<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\ItemStock;
use App\Models\ItemTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class GoodsReceiptService
{
    public function createDraft(PurchaseOrder $po, array $data, User $user): GoodsReceipt
    {
        return DB::transaction(function () use ($po, $data, $user) {

            $gr = GoodsReceipt::create([
                'gr_number' => $this->generateNumber($po->location_id),
                'purchase_order_id' => $po->id,
                'location_id' => $po->location_id,
                'created_by' => $user->id,
                'received_at' => now(),
                'status' => 'draft'
            ]);

            foreach ($data['items'] as $row) {

                $poItem = $po->items()->findOrFail($row['purchase_order_item_id']);

                $qtyReceived = $row['qty_received'] ?? 0;
                $qtyRejected = $row['qty_rejected'] ?? 0;

                $remaining = $poItem->qty_ordered - ($poItem->qty_received + $poItem->qty_rejected);
                $processed = $qtyReceived + $qtyRejected;

                if ($processed <= 0) {
                    throw new Exception('Qty invalid.');
                }

                if ($processed > $remaining) {
                    throw new Exception('Exceeds remaining PO quantity.');
                }

                $unitCost = $poItem->unit_price;

                $subtotal = $qtyReceived * $unitCost;

                $gr->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'item_id' => $poItem->item_id,
                    'qty_received' => $qtyReceived,
                    'qty_rejected' => $qtyRejected,
                    'reject_reason' => $row['reject_reason'] ?? null,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                    'expiry_date' => $row['expiry_date'] ?? null
                ]);
            }

            return $gr->load('items');
        });
    }

    public function complete(GoodsReceipt $gr)
    {
        if ($gr->status !== 'draft') {
            throw new Exception('GR already completed.');
        }

        return DB::transaction(function () use ($gr) {

            $po = $gr->purchaseOrder()
                ->with('items')
                ->lockForUpdate()
                ->first();

            foreach ($gr->items as $item) {

                if ($item->qty_received > 0) {

                    $stock = ItemStock::create([
                        'item_id' => $item->item_id,
                        'location_id' => $gr->location_id,
                        'batch_number' => $gr->gr_number,
                        'quantity' => $item->qty_received,
                        'expiry_date' => $item->expiry_date
                    ]);

                    ItemTransaction::create([
                        'item_id' => $item->item_id,
                        'item_stock_id' => $stock->id,
                        'type' => 'in',
                        'quantity' => $item->qty_received,
                        'reference_type' => 'goods_receipt',
                        'reference_id' => $gr->id,
                    ]);
                }

                $poItem = $po->items
                    ->where('id', $item->purchase_order_item_id)
                    ->first();

                $poItem->increment('qty_received', $item->qty_received);
                $poItem->increment('qty_rejected', $item->qty_rejected);
            }

            $gr->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            return $gr->fresh();
        });
    }

    private function generateNumber($locationId)
    {
        $count = GoodsReceipt::where('location_id', $locationId)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return 'GR/' . $locationId . '/' . now()->format('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}