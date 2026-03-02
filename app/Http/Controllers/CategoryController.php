<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return Category::where('location_id', $request->user()->location_id)
                       ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        return Category::create([
            'location_id' => $request->user()->location_id,
            'name' => $request->name,
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = Category::where('location_id', $request->user()->location_id)
                            ->findOrFail($id);

        $request->validate([
            'name' => 'required'
        ]);

        $category->update([
            'name' => $request->name
        ]);

        return $category;
    }

    public function destroy(Request $request, $id)
    {
        $category = Category::where('location_id', $request->user()->location_id)
                            ->findOrFail($id);

        $category->delete();

        return response()->json(['message' => 'Deleted']);
    }
}