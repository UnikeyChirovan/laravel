<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UploadController extends Controller
{
    public function uploadAvatar(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'file' => 'required|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("avatars/{$user->id}", $filename, 'public');

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
}