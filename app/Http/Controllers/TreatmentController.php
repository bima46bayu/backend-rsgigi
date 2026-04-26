<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST TREATMENTS
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        return Treatment::with('items')
            ->where('location_id', $request->user()->location_id)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE TREATMENT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1'
        ]);

        $treatment = Treatment::create([
            'location_id' => $request->user()->location_id,
            'code'        => $request->code,
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => true
        ]);

        if ($request->items) {
            foreach ($request->items as $item) {
                $treatment->items()->attach($item['id'], [
                    'quantity' => $item['quantity']
                ]);
            }
        }

        return $treatment->load('items');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE TREATMENT
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $treatment = Treatment::where('location_id', $request->user()->location_id)
                              ->findOrFail($id);

        $treatment->update([
            'code'        => $request->code,
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => $request->is_active ?? true
        ]);

        if ($request->items) {
            $syncData = [];

            foreach ($request->items as $item) {
                $syncData[$item['id']] = [
                    'quantity' => $item['quantity']
                ];
            }

            $treatment->items()->sync($syncData);
        }

        return $treatment->load('items');
    }

    /*
    |--------------------------------------------------------------------------
    | DEACTIVATE (SOFT DELETE STYLE)
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, $id)
    {
        $treatment = Treatment::where('location_id', $request->user()->location_id)
                              ->findOrFail($id);

        $treatment->update(['is_active' => false]);

        return response()->json([
            'message' => 'Treatment dinonaktifkan'
        ]);
    }
}