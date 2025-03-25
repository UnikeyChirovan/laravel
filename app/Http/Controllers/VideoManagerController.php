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
        ]);

        $videos = [];
        $files = is_array($request->file('videos')) ? $request->file('videos') : [$request->file('videos')];
        $names = is_array($request->video_names) ? $request->video_names : [$request->video_names];

        foreach ($files as $index => $file) {
            $path = $file->store('videomanager', 'public'); 
            $videoName = $names[$index] ?? "Untitled";

            $videos[] = VideoManager::create([
                'video_name' => $videoName,
                'video_path' => $path,
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
        ]);

        $video = VideoManager::findOrFail($id);
        $video->update([
            'video_name' => $request->video_name,
        ]);

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