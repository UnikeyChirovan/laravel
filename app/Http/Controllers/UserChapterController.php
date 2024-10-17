<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\UserChapter;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserChapterController extends Controller
{
public function storeCurrentChapter(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate();

    $request->validate([
        'chapter_id' => 'required|exists:chapters,id',
    ]);

    $existingRecord = UserChapter::where('user_id', $user->id)
        ->where('chapter_id', $request->chapter_id)
        ->first();

    if ($existingRecord) {
        return response()->json([
            'message' => 'Chương này đã được lưu trước đó.'
        ], 200);
    }

    $userChapter = UserChapter::create([
        'user_id' => $user->id,
        'chapter_id' => $request->chapter_id,
    ]);

    return response()->json($userChapter, 201);
}
public function updateCurrentChapter(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate();

    $request->validate([
        'chapter_id' => 'required|exists:chapters,id',
    ]);

    $userChapter = UserChapter::where('user_id', $user->id)->first();

    if ($userChapter) {
        $userChapter->chapter_id = $request->chapter_id;
        $userChapter->save();

        return response()->json([
            'message' => 'Cập nhật chương thành công.',
            'data' => $userChapter
        ], 200);
    }

    return response()->json([
        'message' => 'Người dùng chưa có chương nào được lưu trước đó.'
    ], 404);
}
public function getLastReadChapter()
{
    $user = JWTAuth::parseToken()->authenticate();

    $userChapter = UserChapter::where('user_id', $user->id)->latest()->first();

    if ($userChapter) {
        $chapter = Chapter::find($userChapter->chapter_id);
        return response()->json($chapter, 200);
    }
    return response()->json([
        'message' => 'Người dùng chưa đọc chương nào.'
    ], 404);
}


}
