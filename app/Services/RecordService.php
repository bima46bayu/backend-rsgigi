<?php

namespace App\Services;

use App\Models\Record;
use App\Models\RecordItem;
use App\Models\RecordTreatment;
use App\Models\Treatment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Exception;

class RecordService
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CREATE RECORD (DRAFT)
    |--------------------------------------------------------------------------
    */

    public function createDraft(int $locationId, array $treatments, ?string $patientName)
    {
        return DB::transaction(function () use ($locationId, $treatments, $patientName) {

            $record = Record::create([
                'code' => 'REC-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'location_id' => $locationId,
                'patient_name' => $patientName,
                'status' => 'draft'
            ]);

            foreach ($treatments as $treatmentId) {

                $rt = RecordTreatment::create([
                    'record_id' => $record->id,
                    'treatment_id' => $treatmentId
                ]);

                $treatment = Treatment::with('items')->findOrFail($treatmentId);

                foreach ($treatment->items as $item) {

                    RecordItem::create([
                        'record_id' => $record->id,
                        'record_treatment_id' => $rt->id,
                        'item_id' => $item->id,
                        'quantity' => $item->pivot->quantity
                    ]);
                }
            }

            return $record->load('items', 'treatments');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ITEMS
    |--------------------------------------------------------------------------
    */

    public function syncItems(int $recordId, array $items)
    {
        return DB::transaction(function () use ($recordId, $items) {

            $record = Record::with('items')->findOrFail($recordId);

            if ($record->status !== 'draft') {
                throw new Exception('Record sudah final dan tidak bisa diubah.');
            }

            $record->items()->delete();

            foreach ($items as $item) {

                if (!isset($item['id'], $item['quantity'])) {
                    throw new Exception('Format item tidak valid.');
                }

                if ($item['quantity'] <= 0) {
                    continue;
                }

                RecordItem::create([
                    'record_id' => $record->id,
                    'record_treatment_id' => $item['record_treatment_id'] ?? null,
                    'item_id'   => $item['id'],
                    'quantity'  => $item['quantity']
                ]);
            }

            return $record->fresh()->load('items');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE RECORD (FIFO STOCK OUT)
    |--------------------------------------------------------------------------
    */

    public function complete(int $recordId)
    {
        return DB::transaction(function () use ($recordId) {

            $record = Record::with('items.item')
                ->lockForUpdate()
                ->findOrFail($recordId);

            if ($record->status !== 'draft') {
                throw new Exception('Record tidak dalam status draft.');
            }

            $insufficientItems = [];

            foreach ($record->items as $recordItem) {

                if ($recordItem->item->type === 'non-stock') {
                    continue;
                }

                $totalStock = $this->inventoryService->getTotalStock(
                    $record->location_id,
                    $recordItem->item_id
                );

                if ($totalStock < $recordItem->quantity) {

                    $insufficientItems[] = [
                        'item_id'   => $recordItem->item_id,
                        'requested' => $recordItem->quantity,
                        'available' => $totalStock
                    ];
                }
            }

            if (!empty($insufficientItems)) {

                throw ValidationException::withMessages([
                    'stock' => $insufficientItems
                ]);
            }

            foreach ($record->items as $recordItem) {

                if ($recordItem->item->type === 'non-stock') {
                    continue;
                }

                $this->inventoryService->stockOut(
                    $record->location_id,
                    $recordItem->item_id,
                    $recordItem->quantity,
                    'record',
                    $record->id
                );
            }

            $record->update([
                'status'       => 'completed',
                'performed_at' => now()
            ]);

            return $record->fresh()->load('items');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT RECORD (RETURN STOCK)
    |--------------------------------------------------------------------------
    */

    public function reject(int $recordId)
    {
        return DB::transaction(function () use ($recordId) {

            $record = Record::with('items.item')
                ->lockForUpdate()
                ->findOrFail($recordId);

            if ($record->status !== 'completed') {
                throw new Exception('Hanya record completed yang bisa direject.');
            }

            foreach ($record->items as $recordItem) {

                if ($recordItem->item->type === 'non-stock') {
                    continue;
                }

                $this->inventoryService->stockIn(
                    $record->location_id,
                    $recordItem->item_id,
                    $recordItem->quantity,
                    'record_reversal',
                    $record->id
                );
            }

            $record->update([
                'status' => 'cancelled'
            ]);

            return $record->fresh()->load('items');
        });
    }
}