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

    public function index(Request $request)
    {
        return Record::with('items')
            ->where('location_id', $request->user()->location_id)
            ->get();
    }

    public function store(Request $request)
    {
        return $this->recordService->createDraft(
            $request->user()->location_id,
            $request->treatment_id,
            $request->patient_name
        );
    }

    public function updateItems(Request $request, $id)
    {
        return $this->recordService->syncItems($id, $request->items);
    }

    public function complete($id)
    {
        return $this->recordService->complete($id);
    }
}