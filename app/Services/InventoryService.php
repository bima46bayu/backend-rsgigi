<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemStock;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    /*
    |--------------------------------------------------------------------------
    | INITIAL STOCK
    |--------------------------------------------------------------------------
    */
    public function setInitialStock(
        int $itemId,
        int $quantity,
        ?string $batchNumber = null,
        ?string $expiryDate = null
    ): void {

        $this->stockIn(
            $itemId,
            $quantity,
            $batchNumber,
            $expiryDate,
            'initial_stock',
            null
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK IN (Purchase / GR)
    |--------------------------------------------------------------------------
    */

    public function stockIn(
        int $itemId,
        int $quantity,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        DB::transaction(function () use (
            $itemId,
            $quantity,
            $batchNumber,
            $expiryDate,
            $referenceType,
            $referenceId
        ) {

            $item = Item::lockForUpdate()->findOrFail($itemId);

            if ($item->type === 'non-stock') {
                throw new Exception('Item non-stock tidak bisa ditambahkan stok.');
            }

            $batch = $item->stocks()->create([
                'batch_number' => $batchNumber,
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
    | STOCK OUT (FIFO)
    |--------------------------------------------------------------------------
    */

    public function stockOut(
        int $itemId,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        DB::transaction(function () use (
            $itemId,
            $quantity,
            $referenceType,
            $referenceId
        ) {

            $item = Item::lockForUpdate()->with('stocks')->findOrFail($itemId);

            if ($item->type === 'non-stock') {
                throw new Exception('Item non-stock tidak memiliki stok.');
            }

            $totalStock = $item->stocks()->sum('quantity');

            if ($totalStock < $quantity) {
                throw new Exception('Stok tidak mencukupi.');
            }

            $remaining = $quantity;

            // FIFO berdasarkan created_at
            $batches = $item->stocks()
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

            $item->increment('version');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | MANUAL ADJUSTMENT (Optional)
    |--------------------------------------------------------------------------
    */

    public function adjustStock(
        int $itemId,
        int $quantity,
        string $type, // in / out
        string $note = 'manual_adjustment'
    ): void {
        if (!in_array($type, ['in', 'out'])) {
            throw new Exception('Tipe tidak valid.');
        }

        if ($type === 'in') {
            $this->stockIn($itemId, $quantity, null, null, $note, null);
        } else {
            $this->stockOut($itemId, $quantity, $note, null);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET TOTAL STOCK
    |--------------------------------------------------------------------------
    */

    public function getTotalStock(int $itemId): int
    {
        $item = Item::with('stocks')->findOrFail($itemId);

        return $item->stocks()->sum('quantity');
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK LOW STOCK (Static min_stock)
    |--------------------------------------------------------------------------
    */

    public function isBelowMinStock(int $itemId): bool
    {
        $item = Item::with('stocks')->findOrFail($itemId);

        $totalStock = $item->stocks()->sum('quantity');

        return $totalStock <= $item->min_stock;
    }
}