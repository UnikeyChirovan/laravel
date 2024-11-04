<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\UserChapter;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserChapterController extends Controller
{
public function saveOrUpdateCurrentChapter(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate();

    $request->validate([
        'chapter_id' => 'required|exists:chapters,id',
    ]);

    // Tìm kiếm bản ghi theo user_id
    $userChapter = UserChapter::where('user_id', $user->id)->first();

    if ($userChapter) {
        // Nếu đã tồn tại bản ghi và chapter_id trùng với giá trị mới, không cần cập nhật
        if ($userChapter->chapter_id == $request->chapter_id) {
            return response()->json([
                'message' => 'Chương đã được cập nhật trước đó.'
            ], 200);
        }
        
        // Nếu chapter_id khác, thì cập nhật giá trị mới
        $userChapter->chapter_id = $request->chapter_id;
        $userChapter->save();

        return response()->json([
            'message' => 'Cập nhật chương thành công.',
            'data' => $userChapter
        ], 200);
    } else {
        // Nếu chưa có bản ghi, tạo mới
        $userChapter = UserChapter::create([
            'user_id' => $user->id,
            'chapter_id' => $request->chapter_id,
        ]);

        return response()->json([
            'message' => 'Lưu chương mới thành công.',
            'data' => $userChapter
        ], 201);
    }
}


public function getLastReadChapter()
{
    $user = JWTAuth::parseToken()->authenticate();

    $userChapter = UserChapter::where('user_id', $user->id)->latest()->first();

    if ($userChapter) {
        return response()->json([
            'chapter_id' => $userChapter->chapter_id
        ], 200);
    }
    
    return response()->json([
        'message' => 'Người dùng chưa đọc chương nào.'
    ], 404);
}


}
