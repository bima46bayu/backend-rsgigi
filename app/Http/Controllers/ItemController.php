<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use App\Services\InventoryService;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
        public function __construct(private InventoryService $inventoryService)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | LIST
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        return Item::with('category')
            ->where('location_id', $request->user()->location_id)
            ->get()
            ->append('total_stock');
    }

    public function show(Request $request, $id)
    {
        return Item::with('category')
            ->where('location_id', $request->user()->location_id)
            ->findOrFail($id)
            ->append('total_stock');
    }

    public function stocks(Request $request, $id)
    {
        $item = Item::where('location_id', $request->user()->location_id)
            ->findOrFail($id);

        return $item->stocks()
            ->where('location_id', $request->user()->location_id)
            ->where('quantity', '>', 0)
            ->orderBy('created_at')
            ->paginate(20);
    }

    public function transactions(Request $request, $id)
    {
        $item = Item::where('location_id', $request->user()->location_id)
            ->findOrFail($id);

        $paginator = $item->transactions()
            ->with('stock')
            ->latest()
            ->paginate(20);

        $paginator->getCollection()->transform(function ($transaction) {
            $code = $transaction->reference_id ? ('#' . $transaction->reference_id) : '';
            if ($transaction->reference_type === 'record') {
                $record = \App\Models\Record::find($transaction->reference_id);
                if ($record) $code = $record->code;
            } elseif ($transaction->reference_type === 'goods_receipt') {
                $gr = \App\Models\GoodsReceipt::with('purchaseOrder')->find($transaction->reference_id);
                if ($gr && $gr->purchaseOrder) {
                    $code = $gr->purchaseOrder->po_number;
                } elseif ($gr) {
                    $code = $gr->gr_number;
                }
            }
            $arr = $transaction->toArray();
            $arr['reference_code'] = $code;
            return $arr;
        });

        return $paginator;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE ITEM + OPTIONAL INITIAL STOCK
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name'          => [
                'required',
                Rule::unique('items')->where(fn ($q) => $q->where('location_id', $request->user()->location_id))
            ],
            'category_id'   => 'required|exists:categories,id',
            'type'          => 'required|in:stock,non-stock',
            'min_stock'     => 'required|integer|min:0',
            'initial_stock' => 'nullable|integer|min:0',
            'batch_number'  => 'nullable|string',
            'expiry_date'   => 'nullable|date'
        ], [
            'name.unique' => 'Barang dengan nama ini sudah ada di cabang ini.'
        ]);

        $item = Item::create([
            'location_id' => $request->user()->location_id,
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'type'        => $request->type,
            'min_stock'   => $request->min_stock,
        ]);

        // INITIAL STOCK (HANYA JIKA STOCK TYPE)
        if (
            $item->type === 'stock' &&
            $request->initial_stock > 0
        ) {
            $this->inventoryService->stockIn(
                $request->user()->location_id,
                $item->id,
                $request->initial_stock,
                $request->batch_number,
                $request->expiry_date,
                'initial_stock',
                $item->id
            );
        }

        return $item->load('category')->append('total_stock');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE MASTER
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $item = Item::where('location_id', $request->user()->location_id)
                    ->findOrFail($id);

        $request->validate([
            'name'        => [
                'required',
                Rule::unique('items')->where(fn ($q) => $q->where('location_id', $request->user()->location_id))->ignore($id)
            ],
            'category_id' => 'required|exists:categories,id',
            'type'        => 'required|in:stock,non-stock',
            'min_stock'   => 'required|integer|min:0'
        ], [
            'name.unique' => 'Barang dengan nama ini sudah ada di cabang ini.'
        ]);

        $item->update([
            'name'        => $request->name,
            'category_id' => $request->category_id,
            'type'        => $request->type,
            'min_stock'   => $request->min_stock
        ]);

        return $item->load('category')->append('total_stock');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, $id)
    {
        $item = Item::where('location_id', $request->user()->location_id)
                    ->findOrFail($id);

        $item->delete();

        return response()->json(['message' => 'Item deleted']);
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK IN (Dari GR / Purchase)
    |--------------------------------------------------------------------------
    */

    public function stockIn(Request $request, $id)
    {
        $item = Item::where('location_id', $request->user()->location_id)
                    ->findOrFail($id);

        $request->validate([
            'quantity'     => 'required|integer|min:1',
            'batch_number' => 'nullable|string',
            'expiry_date'  => 'nullable|date',
        ]);

        $this->inventoryService->stockIn(
            $request->user()->location_id,
            $item->id,
            $request->quantity,
            $request->batch_number,
            $request->expiry_date,
            'adjust_in',
            $request->reference_id ?? null
        );

        return response()->json(['message' => 'Stock berhasil ditambahkan']);
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK OUT (Dari Tindakan)
    |--------------------------------------------------------------------------
    */

    public function stockOut(Request $request, $id)
    {
        $item = Item::where('location_id', $request->user()->location_id)
                    ->findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $this->inventoryService->stockOut(
            $request->user()->location_id,
            $item->id,
            $request->quantity,
            'adjust_out',
            $request->reference_id ?? null
        );

        return response()->json(['message' => 'Stock berhasil dikurangi']);
    }

    /*
    |--------------------------------------------------------------------------
    | GET TOTAL STOCK
    |--------------------------------------------------------------------------
    */

    public function totalStock(Request $request, $id)
    {
        $item = Item::where('location_id', $request->user()->location_id)
                    ->findOrFail($id);

        $total = $this->inventoryService->getTotalStock(
            $request->user()->location_id,
            $item->id
        );

        return response()->json([
            'item_id' => $item->id,
            'total_stock' => $total
        ]);
    }
}