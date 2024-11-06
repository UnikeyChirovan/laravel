<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use Illuminate\Http\Request;

class HeroSlideController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // app/Http/Controllers/HeroSlideController.php

    public function index()
    {
        $heroSlides = HeroSlide::all();
        $lastUpdated = HeroSlide::max('updated_at');
        return response()->json([
            'heroSlides' => $heroSlides,
            'last_updated' => $lastUpdated,
        ]);
    }

    public function show($id)
    {
        $heroSlide = HeroSlide::find($id);
        if (!$heroSlide) {
            return response()->json(['message' => 'Không tìm thấy thông tin slide'], 404);
        }
        return response()->json($heroSlide);
    }

 public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'image_url' => 'nullable|string',
            'round' => 'nullable|string',
            'button_text' => 'required|string',
        ]);

        $heroSlide = HeroSlide::create($validatedData);
        return response()->json($heroSlide, 201);
    }

    public function update(Request $request, $id)
    {
        $heroSlide = HeroSlide::find($id);
        if (!$heroSlide) {
            return response()->json(['message' => 'Không tìm thấy slide'], 404);
        }

        $validatedData = $request->validate([
            'title' => 'string',
            'description' => 'string',
            'image_url' => 'string',
            'round' => 'nullable|string',
            'button_text' => 'string',
        ]);

        $heroSlide->update($validatedData);
        return response()->json($heroSlide);
    }


    public function destroy($id)
    {
        $heroSlide = HeroSlide::findOrFail($id);
        $heroSlide->delete();
        return response()->json(['message' => 'Xóa thành công']);
    }

}
