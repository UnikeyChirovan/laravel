<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function index()
    {
        $features = Feature::all();
        return response()->json($features);
    }

    public function show($id)
    {
        $feature = Feature::find($id);
        if (!$feature) {
            return response()->json(['message' => 'Không tìm thấy tính năng'], 404);
        }
        return response()->json($feature);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'icon' => 'required|string',
            'icon_class' => 'required|string',
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $feature = Feature::create($validatedData);
        return response()->json($feature, 201);
    }

    public function update(Request $request, $id)
    {
        $feature = Feature::find($id);
        if (!$feature) {
            return response()->json(['message' => 'Không tìm thấy tính năng'], 404);
        }

        $validatedData = $request->validate([
            'icon' => 'string',
            'icon_class' => 'string',
            'title' => 'string',
            'description' => 'string',
        ]);

        $feature->update($validatedData);
        return response()->json($feature);
    }

    public function destroy($id)
    {
        $feature = Feature::findOrFail($id);
        $feature->delete();
        return response()->json(['message' => 'Xóa thành công']);
    }
}
