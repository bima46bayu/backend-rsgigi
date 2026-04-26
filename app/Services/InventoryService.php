<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    /*
    |--------------------------------------------------------------------------
    | STOCK IN
    |--------------------------------------------------------------------------
    */

    public function stockIn(
        int $locationId,
        int $itemId,
        int $quantity,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {

        DB::transaction(function () use (
            $locationId,
            $itemId,
            $quantity,
            $batchNumber,
            $expiryDate,
            $referenceType,
            $referenceId
        ) {

            $item = Item::lockForUpdate()->findOrFail($itemId);

            if ($item->type === 'non-stock') {
                throw new Exception('Item non-stock tidak memiliki stok.');
            }

            // Generate otomatis batch number jika tidak diisi
            $finalBatchNumber = $batchNumber ?: 'ADJUST/' . date('Ymd') . '/' . strtoupper(\Illuminate\Support\Str::random(4));

            $batch = $item->stocks()->create([
                'location_id'  => $locationId,
                'batch_number' => $finalBatchNumber,
                'quantity'     => $quantity,
                'expiry_date'  => $expiryDate,
            ]);

            $item->transactions()->create([
                'item_stock_id' => $batch->id,
                'type'          => 'in',
                'quantity'      => $quantity,
                'reference_type'=> $referenceType,
                'reference_id'  => $referenceId,
            ]);

            $item->increment('version');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK OUT FIFO
    |--------------------------------------------------------------------------
    */

    public function stockOut(
        int $locationId,
        int $itemId,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {

        DB::transaction(function () use (
            $locationId,
            $itemId,
            $quantity,
            $referenceType,
            $referenceId
        ) {

            $item = Item::lockForUpdate()->findOrFail($itemId);

            if ($item->type === 'non-stock') {
                throw new Exception('Item non-stock tidak memiliki stok.');
            }

            $totalStock = $item->stocks()
                ->where('location_id', $locationId)
                ->sum('quantity');

            if ($totalStock < $quantity) {
                throw new Exception('Stok tidak mencukupi.');
            }

            $remaining = $quantity;

            $batches = $item->stocks()
                ->where('location_id', $locationId)
                ->where('quantity', '>', 0)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {

                if ($remaining <= 0) break;

                $deduct = min($batch->quantity, $remaining);

                $batch->decrement('quantity', $deduct);

                $item->transactions()->create([
                    'item_stock_id' => $batch->id,
                    'type'          => 'out',
                    'quantity'      => $deduct,
                    'reference_type'=> $referenceType,
                    'reference_id'  => $referenceId,
                ]);

                $remaining -= $deduct;
            }

            if ($remaining > 0) {
                throw new Exception('FIFO deduction gagal.');
            }

            $item->increment('version');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | GET TOTAL STOCK
    |--------------------------------------------------------------------------
    */

    public function getTotalStock(int $locationId, int $itemId): int
    {
        return Item::findOrFail($itemId)
            ->stocks()
            ->where('location_id', $locationId)
            ->sum('quantity');
    }

    /*
    |--------------------------------------------------------------------------
    | LOW STOCK CHECK
    |--------------------------------------------------------------------------
    */

    public function isBelowMinStock(int $locationId, int $itemId): bool
    {
        $item = Item::findOrFail($itemId);

        $totalStock = $item->stocks()
            ->where('location_id', $locationId)
            ->sum('quantity');

        return $totalStock <= $item->min_stock;
    }
}