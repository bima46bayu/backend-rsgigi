<?php

namespace App\Services;

use App\Models\Record;
use App\Models\RecordItem;
use App\Models\Treatment;
use Illuminate\Support\Facades\DB;
use Exception;

class RecordService
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE RECORD (DRAFT)
    |--------------------------------------------------------------------------
    */

    public function createDraft(int $locationId, ?int $treatmentId, ?string $patientName)
    {
        return DB::transaction(function () use ($locationId, $treatmentId, $patientName) {

            $record = Record::create([
                'location_id' => $locationId,
                'treatment_id'=> $treatmentId,
                'patient_name'=> $patientName,
                'status'      => 'draft'
            ]);

            // Copy template items jika ada treatment
            if ($treatmentId) {

                $treatment = Treatment::with('items')->findOrFail($treatmentId);

                foreach ($treatment->items as $item) {
                    RecordItem::create([
                        'record_id' => $record->id,
                        'item_id'   => $item->id,
                        'quantity'  => $item->pivot->quantity
                    ]);
                }
            }

            return $record->load('items');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ITEMS (Override / Add / Remove)
    |--------------------------------------------------------------------------
    */

    public function syncItems(int $recordId, array $items)
    {
        return DB::transaction(function () use ($recordId, $items) {

            $record = Record::with('items')->findOrFail($recordId);

            if ($record->status !== 'draft') {
                throw new Exception('Record sudah final, tidak bisa diubah.');
            }

            // Hapus semua dulu
            $record->items()->delete();

            foreach ($items as $item) {
                RecordItem::create([
                    'record_id' => $record->id,
                    'item_id'   => $item['id'],
                    'quantity'  => $item['quantity']
                ]);
            }

            return $record->load('items');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE RECORD (POTONG STOCK)
    |--------------------------------------------------------------------------
    */

    public function complete(int $recordId)
    {
        return DB::transaction(function () use ($recordId) {

            $record = Record::with('items')
                ->lockForUpdate()
                ->findOrFail($recordId);

            if ($record->status !== 'draft') {
                throw new \Exception('Record tidak dalam status draft.');
            }

            $insufficientItems = [];

            // 🔎 STEP 1: VALIDASI SEMUA ITEM DULU
            foreach ($record->items as $recordItem) {

                $totalStock = $this->inventoryService
                    ->getTotalStock($recordItem->item_id);

                if ($totalStock < $recordItem->quantity) {
                    $insufficientItems[] = [
                        'item_id'   => $recordItem->item_id,
                        'requested' => $recordItem->quantity,
                        'available' => $totalStock
                    ];
                }
            }

            // ❌ Jika ada kekurangan → batalkan
            if (!empty($insufficientItems)) {
                throw new \Exception(json_encode([
                    'message' => 'Stock tidak mencukupi',
                    'insufficient_items' => $insufficientItems
                ]));
            }

            // ✅ STEP 2: Kalau semua cukup → potong FIFO
            foreach ($record->items as $recordItem) {

                $this->inventoryService->stockOut(
                    $recordItem->item_id,
                    $recordItem->quantity,
                    'record',
                    $record->id
                );
            }

            $record->update([
                'status' => 'completed',
                'performed_at' => now()
            ]);

            return $record;
        });
    }
}