<?php

namespace App\Http\Controllers;

use App\Models\ImageManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageManagerController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'images' => 'required|array|max:10', 
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'image_names' => 'required|array', 
            'image_names.*' => 'required|string|max:255',
        ]);

        $images = [];
        foreach ($request->file('images') as $index => $file) {
            $path = $file->store('imagemanager', 'public'); 
            $imageName = $request->image_names[$index];
            $image = ImageManager::create([
                'image_name' => $imageName,
                'image_path' => $path,
            ]);
            $images[] = $image;
        }

        return response()->json([
            'message' => 'Hình ảnh đã được tải lên thành công!',
            'images' => $images
        ], 201);
    }

    public function getImages()
    {
        $images = ImageManager::all();
        return response()->json($images, 200);
    }

    public function getImage($id)
    {
        try {
            $image = ImageManager::findOrFail($id);
            return response()->json($image, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Hình ảnh không tìm thấy'], 404);
        }
    }

    public function updateImage(Request $request, $id)
    {
        $request->validate([
            'image_name' => 'required|string|max:255',
        ]);

        $image = ImageManager::findOrFail($id);
        $image->update([
            'image_name' => $request->image_name,
        ]);

        return response()->json([
            'message' => 'Cập nhật thành công!',
            'image' => $image
        ], 200);
    }

    public function deleteImage($id)
    {
        $image = ImageManager::findOrFail($id);
        Storage::delete('public/' . $image->image_path); 
        $image->delete();

        return response()->json([
            'message' => 'Xóa hình ảnh thành công!'
        ], 204);
    }
}
