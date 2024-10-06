<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class UploadController extends Controller
{
    public function uploadAvatar(Request $request)
        {
        $user = Auth::user();
        $request->validate([
            'file' => 'required|image|mimes:jpg,png,jpeg,gif|max:2048', 
            'height' => 'required|numeric', 
            'width' => 'required|numeric',   
            'left' => 'required|numeric',    
            'top' => 'required|numeric',   
        ]);

        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();
                $image = Image::make($file);
                $image->crop(
                    (int) $request->input('width'),
                    (int) $request->input('height'),
                    (int) $request->input('left'),
                    (int) $request->input('top')
                );
                $path = "avatars/{$user->id}/{$filename}";
                Storage::disk('public')->put($path, (string) $image->encode());
                if ($user->avatar) {
                    Storage::disk('public')->delete("avatars/{$user->id}/" . $user->avatar);
                }
                $user->avatar = $filename;
                $user->save();
                $url = Storage::url($path);
                return response()->json(['url' => $url], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Lỗi khi upload avatar: ' . $e->getMessage()], 500);
            }
        }
        return response()->json(['error' => 'Không có tệp nào được tải lên'], 400);
        }

    public function uploadCover(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'file' => 'required|image|mimes:jpg,png,jpeg,gif|max:2048',
            'position' => 'required|numeric', 
        ]);

        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $filename = 'cover_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("covers/{$user->id}", $filename, 'public');
                if ($user->cover) {
                    Storage::disk('public')->delete("covers/{$user->id}/" . $user->cover);
                }
                $user->cover = $filename;
                $user->cover_position = $request->position;
                $user->save();
                $url = Storage::url($path);
                return response()->json(['url' => $url, 'positionY' => $user->cover_position], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Lỗi khi upload cover: ' . $e->getMessage()], 500);
            }
        }
        return response()->json(['error' => 'Không có tệp nào được tải lên'], 400);
    }
    public function updateCoverPosition(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'position' => 'required|numeric',
        ]);

        try {
            $user->cover_position = $request->position;
            $user->save();

            return response()->json(['positionY' => $user->cover_position], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Lỗi khi cập nhật vị trí cover: ' . $e->getMessage()], 500);
        }
    }
     public function deleteAvatar($id)
    {
        $user = User::findOrFail($id);
        if ($user->avatar) {
            Storage::disk('public')->delete("avatars/{$id}/{$user->avatar}");
            $user->avatar = null;
            $user->save();
            return response()->json(['message' => 'Avatar deleted successfully']);
        }
        return response()->json(['message' => 'No avatar to delete'], 404);
    }

    // Xóa cover
    public function deleteCover($id)
    {
        $user = User::findOrFail($id);
        if ($user->cover) {
            Storage::disk('public')->delete("covers/{$id}/{$user->cover}");
            $user->cover = null;
            $user->save();
            return response()->json(['message' => 'Cover deleted successfully']);
        }
        return response()->json(['message' => 'No cover to delete'], 404);
    }

    // admin
    public function createChapter(Request $request)
    {
        $request->validate([
            'chapter_number' => 'required|integer',
            'title' => 'required|string',
            'author' => 'required|string',
            'content' => 'required|array', 
        ]);
        $fullContent = implode("\n\n", $request->input('content'));
        $filename = 'chapter-' . $request->input('chapter_number') . '.txt';
        $path = 'stories/' . $filename;
        Storage::put($path, $fullContent);
        $chapter = Chapter::create([
            'title' => $request->input('title'),
            'author' => $request->input('author'),
            'chapter_number' => $request->input('chapter_number'),
            'file_path' => $path, 
        ]);
        return response()->json($chapter, 201);
    }
    public function index()
    {
        return Chapter::all();
    }
    public function destroy($id)
    {
        $chapter = Chapter::findOrFail($id);
        if ($chapter->file_path) {
            Storage::delete($chapter->file_path); 
        }
        $chapter->delete();
        return response()->json(null, 204);
    }
}
