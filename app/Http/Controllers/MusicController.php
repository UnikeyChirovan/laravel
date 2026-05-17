<?php

namespace App\Http\Controllers;

use App\Models\MusicAlbum;
use App\Models\MusicTrack;
use App\Models\UserFavoriteTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class MusicController extends Controller
{
    // ========== USER ENDPOINTS ==========
    
    // Lấy tất cả albums với tracks
    public function getAlbums()
    {
        $albums = MusicAlbum::where('is_active', true)
            ->orderBy('order')
            ->with(['activeTracks' => function($query) {
                $query->select('id', 'album_id', 'title', 'artist', 'file_path', 'duration', 'order');
            }])
            ->get();

        return response()->json($albums);
    }

    // Lấy tracks của một album
    public function getAlbumTracks($albumId)
    {
        $tracks = MusicTrack::where('album_id', $albumId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json($tracks);
    }

    // Lấy playlist yêu thích của user
    public function getFavorites()
    {
        $userId = Auth::id();
        
        $favorites = UserFavoriteTrack::where('user_id', $userId)
            ->orderBy('order')
            ->with('track')
            ->get()
            ->map(function($fav) {
                return $fav->track;
            });

        return response()->json($favorites);
    }

    // Thêm bài hát vào yêu thích
    public function addFavorite(Request $request)
    {
        $request->validate([
            'track_id' => 'required|exists:music_tracks,id'
        ]);

        $userId = Auth::id();
        
        // Kiểm tra xem đã tồn tại chưa
        $exists = UserFavoriteTrack::where('user_id', $userId)
            ->where('track_id', $request->track_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Bài hát đã có trong danh sách yêu thích'], 400);
        }

        // Lấy order cao nhất
        $maxOrder = UserFavoriteTrack::where('user_id', $userId)->max('order') ?? -1;

        $favorite = UserFavoriteTrack::create([
            'user_id' => $userId,
            'track_id' => $request->track_id,
            'order' => $maxOrder + 1
        ]);

        return response()->json([
            'message' => 'Đã thêm vào yêu thích',
            'favorite' => $favorite->load('track')
        ], 201);
    }

    // Xóa khỏi yêu thích
    public function removeFavorite($trackId)
    {
        $userId = Auth::id();
        
        $deleted = UserFavoriteTrack::where('user_id', $userId)
            ->where('track_id', $trackId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Không tìm thấy bài hát trong danh sách yêu thích'], 404);
        }

        return response()->json(['message' => 'Đã xóa khỏi yêu thích'], 200);
    }

    // Sắp xếp lại playlist yêu thích
    public function reorderFavorites(Request $request)
    {
        $request->validate([
            'track_ids' => 'required|array'
        ]);

        $userId = Auth::id();

        foreach ($request->track_ids as $index => $trackId) {
            UserFavoriteTrack::where('user_id', $userId)
                ->where('track_id', $trackId)
                ->update(['order' => $index]);
        }

        return response()->json(['message' => 'Đã cập nhật thứ tự'], 200);
    }

    // ========== ADMIN ENDPOINTS ==========
    
    // Lấy tất cả albums (bao gồm inactive)
    public function adminGetAlbums()
    {
        $albums = MusicAlbum::orderBy('order')
            ->withCount('tracks')
            ->get();

        return response()->json($albums);
    }

    // Tạo album mới
    public function createAlbum(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        $data = $request->only(['name', 'description', 'order', 'is_active']);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('music/covers', 'public');
            $data['cover_image'] = $path;
        }

        $album = MusicAlbum::create($data);

        return response()->json($album, 201);
    }

    // Cập nhật album
    public function updateAlbum(Request $request, $id)
    {
        $album = MusicAlbum::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        $data = $request->only(['name', 'description', 'order', 'is_active']);

        if ($request->hasFile('cover_image')) {
            // Xóa ảnh cũ
            if ($album->cover_image) {
                Storage::disk('public')->delete($album->cover_image);
            }
            
            $path = $request->file('cover_image')->store('music/covers', 'public');
            $data['cover_image'] = $path;
        }

        $album->update($data);

        return response()->json($album, 200);
    }

    // Xóa album
    public function deleteAlbum($id)
    {
        $album = MusicAlbum::findOrFail($id);

        // Xóa cover image
        if ($album->cover_image) {
            Storage::disk('public')->delete($album->cover_image);
        }

        // Xóa tất cả tracks và files của album
        foreach ($album->tracks as $track) {
            if ($track->file_path) {
                Storage::disk('public')->delete($track->file_path);
            }
        }

        $album->delete();

        return response()->json(['message' => 'Đã xóa album'], 200);
    }

    // Lấy tất cả tracks
    public function adminGetTracks()
    {
        $tracks = MusicTrack::with('album:id,name')
            ->orderBy('album_id')
            ->orderBy('order')
            ->get();

        return response()->json($tracks);
    }

    // Tạo track mới
    public function createTrack(Request $request)
    {
        $request->validate([
            'album_id' => 'required|exists:music_albums,id',
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'file' => 'required|mimes:mp3,wav|max:10240', // 10MB
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        $data = $request->only(['album_id', 'title', 'artist', 'order', 'is_active']);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('music/tracks', 'public');
            $data['file_path'] = $path;

            // Lấy duration (cần extension getID3 hoặc ffmpeg)
            // Đơn giản hóa: set null, admin có thể update sau
            $data['duration'] = null;
        }

        $track = MusicTrack::create($data);

        return response()->json($track->load('album'), 201);
    }

    // Cập nhật track
    public function updateTrack(Request $request, $id)
    {
        $track = MusicTrack::findOrFail($id);

        $request->validate([
            'album_id' => 'required|exists:music_albums,id',
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'file' => 'nullable|mimes:mp3,wav|max:10240',
            'duration' => 'nullable|integer',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        $data = $request->only(['album_id', 'title', 'artist', 'duration', 'order', 'is_active']);

        if ($request->hasFile('file')) {
            // Xóa file cũ
            if ($track->file_path) {
                Storage::disk('public')->delete($track->file_path);
            }
            
            $path = $request->file('file')->store('music/tracks', 'public');
            $data['file_path'] = $path;
        }

        $track->update($data);

        return response()->json($track->load('album'), 200);
    }

    // Xóa track
    public function deleteTrack($id)
    {
        $track = MusicTrack::findOrFail($id);

        // Xóa file
        if ($track->file_path) {
            Storage::disk('public')->delete($track->file_path);
        }

        $track->delete();

        return response()->json(['message' => 'Đã xóa bài hát'], 200);
    }
}