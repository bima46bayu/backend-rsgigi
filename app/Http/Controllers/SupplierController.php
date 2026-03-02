<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        return Supplier::where('location_id', $request->user()->location_id)
                       ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        return Supplier::create([
            'location_id' => $request->user()->location_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::where('location_id', $request->user()->location_id)
                            ->findOrFail($id);

        $supplier->update($request->only('name', 'phone', 'email', 'address'));

        return $supplier;
    }

    public function destroy(Request $request, $id)
    {
        $supplier = Supplier::where('location_id', $request->user()->location_id)
                            ->findOrFail($id);

        $supplier->delete();

        return response()->json(['message' => 'Deleted']);
    }
}