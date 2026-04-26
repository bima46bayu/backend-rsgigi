<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseService
{
    public function createDraft(array $data, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $user) {

            $po = PurchaseOrder::create([
                'po_number'   => $this->generateNumber($user->location_id),
                'location_id' => $user->location_id,
                'supplier_id' => $data['supplier_id'],
                'created_by'  => $user->id,
                'ordered_at'  => now(),
                'notes'       => $data['notes'] ?? null,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $subtotal = $item['qty'] * $item['unit_price'];
                $total += $subtotal;

                $po->items()->create([
                    'item_id'    => $item['item_id'],
                    'qty_ordered'=> $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $subtotal,
                ]);
            }

            $po->update(['total_amount' => $total]);

            return $po->load('items', 'supplier');
        });
    }

    public function updateDraft(PurchaseOrder $po, array $data)
    {
        if ($po->status !== 'draft') {
            throw new Exception('Only draft can be edited.');
        }

        return DB::transaction(function () use ($po, $data) {

            $po->items()->delete();

            $total = 0;

            foreach ($data['items'] as $item) {
                $subtotal = $item['qty'] * $item['unit_price'];
                $total += $subtotal;

                $po->items()->create([
                    'item_id'    => $item['item_id'],
                    'qty_ordered'=> $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $subtotal,
                ]);
            }

            $po->update([
                'supplier_id' => $data['supplier_id'],
                'notes'       => $data['notes'] ?? null,
                'total_amount'=> $total
            ]);

            return $po->load('items', 'supplier');
        });
    }

    public function approve(PurchaseOrder $po, User $user)
    {
        if ($po->status !== 'draft') {
            throw new Exception('PO already processed.');
        }

        if ($po->items()->count() === 0) {
            throw new Exception('Cannot approve empty PO.');
        }

        $po->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id
        ]);

        return $po;
    }

    public function cancel(PurchaseOrder $po)
    {
        if ($po->status !== 'draft') {
            throw new Exception('Only draft can be cancelled.');
        }

        $po->update(['status' => 'cancelled']);

        return $po;
    }

    private function generateNumber($locationId)
    {
        $count = PurchaseOrder::where('location_id', $locationId)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return 'PO/' . $locationId . '/' . now()->format('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}