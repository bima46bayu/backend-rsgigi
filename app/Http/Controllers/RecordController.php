<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RecordService;
use App\Models\Record;

class RecordController extends Controller
{
    public function __construct(private RecordService $recordService)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | LIST RECORDS (HISTORY)
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        return Record::with(['items', 'treatments.treatment'])
            ->where('location_id', $request->user()->location_id)
            ->latest()
            ->paginate($request->get('limit', 10));
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW RECORD DETAIL
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        return Record::with([
            'items.item',
            'treatments.treatment'
        ])->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE DRAFT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'patient_name' => 'nullable|string',
            'treatments' => 'required|array',
            'treatments.*' => 'exists:treatments,id'
        ]);

        $record = $this->recordService->createDraft(
            $request->user()->location_id, // otomatis dari user login
            $request->treatments,
            $request->patient_name
        );

        return response()->json($record);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ITEMS
    |--------------------------------------------------------------------------
    */

    public function updateItems(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.record_treatment_id' => 'nullable|exists:record_treatments,id'
        ]);

        return $this->recordService->syncItems(
            $id,
            $request->items
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE RECORD
    |--------------------------------------------------------------------------
    */

    public function complete($id)
    {
        return $this->recordService->complete($id);
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT RECORD
    |--------------------------------------------------------------------------
    */

    public function reject($id)
    {
        return $this->recordService->reject($id);
    }
}