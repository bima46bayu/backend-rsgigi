<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use App\Services\InventoryService;

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

    /*
    |--------------------------------------------------------------------------
    | CREATE ITEM + OPTIONAL INITIAL STOCK
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required',
            'category_id'   => 'required|exists:categories,id',
            'type'          => 'required|in:stock,non-stock',
            'min_stock'     => 'required|integer|min:0',
            'initial_stock' => 'nullable|integer|min:0',
            'batch_number'  => 'nullable|string',
            'expiry_date'   => 'nullable|date'
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
            $this->inventoryService->setInitialStock(
                $item->id,
                $request->initial_stock,
                $request->batch_number,
                $request->expiry_date
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
            'name'      => 'required',
            'min_stock' => 'required|integer|min:0'
        ]);

        $item->update([
            'name'      => $request->name,
            'min_stock' => $request->min_stock
        ]);

        return $item;
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
            $item->id,
            $request->quantity,
            $request->batch_number,
            $request->expiry_date,
            'purchase',
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
            $item->id,
            $request->quantity,
            'tindakan',
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

        $total = $this->inventoryService->getTotalStock($item->id);

        return response()->json([
            'item_id' => $item->id,
            'total_stock' => $total
        ]);
    }
}