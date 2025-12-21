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
                
                // Crop avatar
                $image->crop(
                    (int) $request->input('width'),
                    (int) $request->input('height'),
                    (int) $request->input('left'),
                    (int) $request->input('top')
                );
                
                $path = "avatars/{$user->id}/{$filename}";
                Storage::disk('public')->put($path, (string) $image->encode());
                
                // Delete old avatar
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
            'file' => 'required|image|mimes:jpg,png,jpeg,gif|max:5120', // 5MB max
            'height' => 'required|numeric',
            'width' => 'required|numeric',
            'left' => 'required|numeric',
            'top' => 'required|numeric',
        ]);

        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $filename = 'cover_' . time() . '.' . $file->getClientOriginalExtension();
                $image = Image::make($file);
                
                // Crop cover
                $image->crop(
                    (int) $request->input('width'),
                    (int) $request->input('height'),
                    (int) $request->input('left'),
                    (int) $request->input('top')
                );
                
                // Resize to optimal dimensions (1200x400 for cover)
                $image->resize(1200, 400, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                $path = "covers/{$user->id}/{$filename}";
                Storage::disk('public')->put($path, (string) $image->encode());
                
                // Delete old cover
                if ($user->cover) {
                    Storage::disk('public')->delete("covers/{$user->id}/" . $user->cover);
                }
                
                $user->cover = $filename;
                $user->save();
                
                $url = Storage::url($path);
                return response()->json(['url' => $url], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Lỗi khi upload cover: ' . $e->getMessage()], 500);
            }
        }
        
        return response()->json(['error' => 'Không có tệp nào được tải lên'], 400);
    }

    public function deleteAvatar($id)
    {
        $user = User::findOrFail($id);
        if ($user->avatar) {
            Storage::disk('public')->delete("avatars/{$id}/{$user->avatar}");
            $user->avatar = null;
            $user->save();
            return response()->json(['message' => 'Avatar đã được xóa thành công']);
        }
        return response()->json(['message' => 'Không có avatar để xóa'], 404);
    }

    public function deleteCover($id)
    {
        $user = User::findOrFail($id);
        if ($user->cover) {
            Storage::disk('public')->delete("covers/{$id}/{$user->cover}");
            $user->cover = null;
            $user->save();
            return response()->json(['message' => 'Cover đã được xóa thành công']);
        }
        return response()->json(['message' => 'Không có cover để xóa'], 404);
    }
        public function createChapter(Request $request)
    {
        $request->validate([
            'chapter_number' => 'required|integer',
            'title' => 'required|string',
            'content' => 'required|array', 
        ]);
        $fullContent = implode("\n\n", $request->input('content'));
        $filename = 'chapter-' . $request->input('chapter_number') . '.txt';
        $path = 'stories/' . $filename;
        Storage::put($path, $fullContent);
        $chapter = Chapter::create([
            'title' => $request->input('title'),
            'chapter_number' => $request->input('chapter_number'),
            'file_path' => $path, 
        ]);
        return response()->json($chapter, 201);
    }
    public function getChapter($id)
    {
        $chapter = Chapter::findOrFail($id);
                if (Storage::exists($chapter->file_path)) {
            $content = Storage::get($chapter->file_path);
            $chapter->content = explode("\n\n", $content);
        } else {
            $chapter->content = [];
        }
        return response()->json($chapter);
    }

    public function updateChapter(Request $request, $id)
    {
        $request->validate([
            'chapter_number' => 'required|integer',
            'title' => 'required|string',
            'author' => 'required|string',
            'content' => 'required|array',
        ]);
        $chapter = Chapter::findOrFail($id);
        $fullContent = implode("\n\n", $request->input('content'));
        $filename = 'chapter-' . $request->input('chapter_number') . '.txt';
        $path = 'stories/' . $filename;
        if ($chapter->file_path !== $path) {
            Storage::delete($chapter->file_path);
        }
        Storage::put($path, $fullContent);
        $chapter->update([
            'title' => $request->input('title'),
            'author' => $request->input('author'),
            'chapter_number' => $request->input('chapter_number'),
            'file_path' => $path,
        ]);
        return response()->json($chapter, 200);
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