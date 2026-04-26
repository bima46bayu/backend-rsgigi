<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceiptService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Http\Controllers\Controller;

class GoodsReceiptController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST GR (For Table)
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = GoodsReceipt::with([
                'purchaseOrder:id,po_number'
            ])
            ->withSum('items as qty_received', 'qty_received')
            ->withSum('items as qty_rejected', 'qty_rejected')
            ->where('location_id', $user->location_id);

        // Filter status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by PO
        if ($request->purchase_order_id) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        $grs = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $grs
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL GR
    |--------------------------------------------------------------------------
    */
    public function show(GoodsReceipt $goodsReceipt)
    {
        $this->authorizeLocation($goodsReceipt);

        $goodsReceipt->load([
            'purchaseOrder:id,po_number',
            'items.item:id,name'
        ]);

        $goodsReceipt->loadSum('items as qty_received', 'qty_received');
        $goodsReceipt->loadSum('items as qty_rejected', 'qty_rejected');

        return response()->json([
            'success' => true,
            'data' => $goodsReceipt
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE DRAFT GR
    |--------------------------------------------------------------------------
    */
    public function store(StoreGoodsReceiptRequest $request, PurchaseOrder $purchase, GoodsReceiptService $service)
    {
        $this->authorizeLocation($purchase);

        $gr = $service->createDraft($purchase, $request->validated(), auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Draft Goods Receipt berhasil dibuat',
            'data' => $gr
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE & ADD STOCK
    |--------------------------------------------------------------------------
    | Memicu penambahan stok ke inventory berdasarkan item di GR
    */
    public function complete(GoodsReceipt $goodsReceipt, GoodsReceiptService $service)
    {
        $this->authorizeLocation($goodsReceipt);

        if ($goodsReceipt->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya draft GR yang dapat diselesaikan'
            ], 422);
        }

        $service->complete($goodsReceipt);

        return response()->json([
            'success' => true,
            'message' => 'Penerimaan barang selesai & stok telah ditambahkan',
            'data' => [
                'id' => $goodsReceipt->id,
                'status' => 'completed'
            ]
        ]);
    }
}