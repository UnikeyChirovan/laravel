<?php

namespace App\Http\Controllers;

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

        $userChapter = UserChapter::where('user_id', $user->id)->first();

        if ($userChapter) {
            if ($userChapter->chapter_id == $request->chapter_id) {
                return response()->json([
                    'message' => 'Chương đã được cập nhật trước đó.'
                ], 200);
            }
            
            $userChapter->chapter_id = $request->chapter_id;
            $userChapter->save();

            return response()->json([
                'message' => 'Cập nhật chương thành công.',
                'data' => $userChapter
            ], 200);
        } else {
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

    public function saveOrUpdateCurrentEpisode(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'episode_id' => 'required|exists:video_managers,id',
        ]);

        $userChapter = UserChapter::where('user_id', $user->id)->first();

        if ($userChapter) {
            if ($userChapter->episode_id == $request->episode_id) {
                return response()->json([
                    'message' => 'Tập đã được cập nhật trước đó.'
                ], 200);
            }
            
            $userChapter->episode_id = $request->episode_id;
            $userChapter->save();

            return response()->json([
                'message' => 'Cập nhật tập phim thành công.',
                'data' => $userChapter
            ], 200);
        } else {
            $userChapter = UserChapter::create([
                'user_id' => $user->id,
                'episode_id' => $request->episode_id,
            ]);

            return response()->json([
                'message' => 'Lưu tập phim mới thành công.',
                'data' => $userChapter
            ], 201);
        }
    }

    public function getLastWatchEpisode()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $userChapter = UserChapter::where('user_id', $user->id)->latest()->first();

        if ($userChapter && $userChapter->episode_id) {
            return response()->json([
                'episode_id' => $userChapter->episode_id
            ], 200);
        }
        
        return response()->json([
            'message' => 'Người dùng chưa xem tập nào.'
        ], 404);
    }
public function getGuestLastWatchEpisode(Request $request, $id)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
    } catch (\Exception $e) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $userChapter = UserChapter::where('user_id', $id)->latest()->first();

    if ($userChapter && $userChapter->episode_id) {
        return response()->json([
            'episode_id' => $userChapter->episode_id
        ], 200);
    }
    
    return response()->json([
        'message' => 'Người dùng chưa xem tập nào.'
    ], 404);
}

public function getGuestLastReadChapter(Request $request, $id)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
    } catch (\Exception $e) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $userChapter = UserChapter::where('user_id', $id)->latest()->first();

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
