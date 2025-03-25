<?php

namespace App\Http\Controllers;

use App\Models\VideoManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoManagerController extends Controller
{
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'videos' => 'required',
            'videos.*' => 'mimes:mp4,mov,avi|max:204800',
            'video_names' => 'required',
            'video_names.*' => 'string|max:255',
            'descriptions' => 'nullable',
            'descriptions.*' => 'string|max:1000',
            'thumbnails' => 'nullable|array',
            'thumbnails.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB   
        ]);

        $videos = [];
        $files = is_array($request->file('videos')) ? $request->file('videos') : [$request->file('videos')];
        $names = is_array($request->video_names) ? $request->video_names : [$request->video_names];
        $descriptions = is_array($request->descriptions) ? $request->descriptions : [$request->descriptions];
        $thumbnails = $request->file('thumbnails') ?? [];

        foreach ($files as $index => $file) {
            $path = $file->store('videomanager', 'public'); 
            $videoName = $names[$index] ?? "Untitled";
            $description = $descriptions[$index] ?? null;
            $thumbnailPath = null;
            if (isset($thumbnails[$index])) {
                $thumbnailPath = $thumbnails[$index]->store('thumbnails', 'public');
            }

            $videos[] = VideoManager::create([
                'video_name' => $videoName,
                'video_path' => $path,
                'description' => $description,
                'thumbnail' => $thumbnailPath,
            ]);
        }

        return response()->json([
            'message' => 'Video đã được tải lên thành công!',
            'videos' => $videos
        ], 201);
    }

    public function getVideos()
    {
        $videos = VideoManager::all();
        return response()->json($videos, 200);
    }

    public function getVideo($id)
    {
        try {
            $video = VideoManager::findOrFail($id);
            return response()->json($video, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Video không tìm thấy'], 404);
        }
    }

    public function updateVideo(Request $request, $id)
    {
        $request->validate([
            'video_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $video = VideoManager::findOrFail($id);
        // Xử lý cập nhật thumbnail
        if ($request->hasFile('thumbnail')) {
            // Xóa thumbnail cũ nếu có
            if ($video->thumbnail && Storage::exists('public/' . $video->thumbnail)) {
                Storage::delete('public/' . $video->thumbnail);
            }

            // Lưu thumbnail mới
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
            $video->update([
                'video_name' => $request->video_name,
                'description' => $request->description,
                'thumbnail' => $thumbnailPath,
            ]);
        } else {
            $video->update([
                'video_name' => $request->video_name,
                'description' => $request->description,
            ]);
        }

        return response()->json([
            'message' => 'Cập nhật thành công!',
            'video' => $video
        ], 200);
    }

    public function deleteVideo($id)
    {
        $video = VideoManager::findOrFail($id);
        if (Storage::exists('public/' . $video->video_path)) {
            Storage::delete('public/' . $video->video_path);
        }
        $video->delete();

        return response()->json(['message' => 'Xóa video thành công!'], 204);
    }

    public function setFeaturedVideo(Request $request, $id)
    {
        // Tìm video hiện tại đang được đặt là đặc biệt
        $currentFeatured = VideoManager::where('is_featured', true)->first();
        if ($currentFeatured) {
            $currentFeatured->update(['is_featured' => false]);
        }

        // Cập nhật video mới
        $video = VideoManager::findOrFail($id);
        $video->update(['is_featured' => true]);

        return response()->json(['message' => 'Video đặc biệt đã được cập nhật!', 'video' => $video], 200);
    }

    public function getFeaturedVideo()
    {
        $video = VideoManager::where('is_featured', true)->first();
        Log::info("Lấy video đặc biệt:", ['video' => $video]);

        if (!$video) {
            return response()->json(['message' => 'Chưa có video đặc biệt nào'], 404);
        }
        return response()->json($video, 200);
    }

}