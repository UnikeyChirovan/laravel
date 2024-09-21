<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
                $file->move(public_path('uploads/avatars'), $filename);
                
                // Xóa avatar cũ nếu có
                if ($user->avatar) {
                    $oldAvatarPath = public_path('uploads/avatars/' . $user->avatar);
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }

                // Cập nhật avatar mới cho user
                $user->avatar = $filename;
                $user->save();

                $url = url('/uploads/avatars/' . $filename);

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
                $file->move(public_path('uploads/covers'), $filename);

                // Xóa cover cũ nếu có
                if ($user->cover) {
                    $oldCoverPath = public_path('uploads/covers/' . $user->cover);
                    if (file_exists($oldCoverPath)) {
                        unlink($oldCoverPath);
                    }
                }

                // Cập nhật cover mới cho user
                $user->cover = $filename;
                $user->save();

                $url = url('/uploads/covers/' . $filename);

                return response()->json(['url' => $url], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Lỗi khi upload cover: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'Không có tệp nào được tải lên'], 400);
    }


    
}
