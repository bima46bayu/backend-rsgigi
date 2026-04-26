<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use App\Http\Requests\StorePurchaseRequest;

class PurchaseController extends Controller
{
    /**
     * @var PurchaseService
     */
    private $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    /*
    |--------------------------------------------------------------------------
    | LIST PO
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = PurchaseOrder::with(['supplier:id,name', 'items.item:id,name'])
            ->withSum('items as qty_received', 'qty_received')
            ->withSum('items as qty_rejected', 'qty_rejected')
            ->where('location_id', $user->location_id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL PO
    |--------------------------------------------------------------------------
    */
    public function show(PurchaseOrder $purchase)
    {
        $this->authorizeLocation($purchase);

        // Load relasi utama
        $purchase->load([
            'supplier:id,name',
            'items.item:id,name',
            'goodsReceipts:id,gr_number,status,received_at'
        ]);

        // Load total sum dari items
        $purchase->loadSum('items as qty_received', 'qty_received');
        $purchase->loadSum('items as qty_rejected', 'qty_rejected');

        return response()->json([
            'success' => true,
            'data' => $purchase
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE DRAFT PO
    |--------------------------------------------------------------------------
    */
    public function store(StorePurchaseRequest $request)
    {
        $po = $this->purchaseService->createDraft($request->validated(), auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Draft Purchase Order berhasil dibuat',
            'data' => $po
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE DRAFT PO
    |--------------------------------------------------------------------------
    */
    public function update(StorePurchaseRequest $request, PurchaseOrder $purchase)
    {
        $this->authorizeLocation($purchase);

        try {
            $po = $this->purchaseService->updateDraft($purchase, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Draft Purchase Order berhasil diperbarui',
                'data' => $po
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE DRAFT PO
    |--------------------------------------------------------------------------
    */
    public function destroy(PurchaseOrder $purchase)
    {
        $this->authorizeLocation($purchase);

        if ($purchase->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya draft yang dapat dihapus'
            ], 422);
        }

        $purchase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order berhasil dihapus'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE PO
    |--------------------------------------------------------------------------
    */
    public function approve(PurchaseOrder $purchase)
    {
        $this->authorizeLocation($purchase);

        try {
            $po = $this->purchaseService->approve($purchase, auth()->user());
            return response()->json([
                'success' => true,
                'message' => 'Purchase Order telah disetujui',
                'data' => $po
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL PO
    |--------------------------------------------------------------------------
    */
    public function cancel(PurchaseOrder $purchase)
    {
        $this->authorizeLocation($purchase);

        try {
            $po = $this->purchaseService->cancel($purchase);
            return response()->json([
                'success' => true,
                'message' => 'Purchase Order berhasil dibatalkan',
                'data' => $po
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}