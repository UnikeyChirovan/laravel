<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Block;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    public function follow($id)
    {
        $user = auth()->user();

        // Ngăn chặn tự follow chính mình
        if ($user->id == $id) {
            return response()->json(['message' => 'Bạn không thể theo dõi chính mình'], 400);
        }

        // Kiểm tra có bị chặn không
        $isBlocked = Block::where(function ($q) use ($user, $id) {
            $q->where('blocker_id', $user->id)->where('blocked_id', $id);
        })->orWhere(function ($q) use ($user, $id) {
            $q->where('blocker_id', $id)->where('blocked_id', $user->id);
        })->exists();

        if ($isBlocked) {
            return response()->json(['message' => 'Không thể theo dõi người dùng này'], 403);
        }

        Follow::firstOrCreate(['follower_id' => $user->id, 'following_id' => $id]);
        return response()->json(['message' => 'Đã theo dõi']);
    }

    public function unfollow($id)
    {
        $user = auth()->user();
        Follow::where('follower_id', $user->id)->where('following_id', $id)->delete();
        return response()->json(['message' => 'Đã hủy theo dõi']);
    }

    public function block($id)
    {
        $user = auth()->user();

        // Ngăn chặn tự block chính mình
        if ($user->id == $id) {
            return response()->json(['message' => 'Bạn không thể tự chặn chính mình'], 400);
        }

        // Ngăn chặn block admin (ID = 1)
        if ($id == 1) {
            return response()->json(['message' => 'Bạn không thể chặn admin'], 403);
        }

        // Xóa quan hệ theo dõi nếu có
        Follow::where(function ($q) use ($user, $id) {
            $q->where('follower_id', $user->id)->where('following_id', $id);
        })->orWhere(function ($q) use ($user, $id) {
            $q->where('follower_id', $id)->where('following_id', $user->id);
        })->delete();

        // Thêm block
        Block::firstOrCreate(['blocker_id' => $user->id, 'blocked_id' => $id]);
        return response()->json(['message' => 'Đã chặn người dùng']);
    }

    public function unblock($id)
    {
        $user = auth()->user();
        Block::where('blocker_id', $user->id)->where('blocked_id', $id)->delete();
        return response()->json(['message' => 'Đã bỏ chặn']);
    }

    public function isFollowing(Request $request, $id)
    {
        $currentUser = $request->user();

        $isFollowing = Follow::where('follower_id', $currentUser->id)
                             ->where('following_id', $id)
                             ->exists();

        return response()->json([
            'following' => $isFollowing
        ]);
    }

    public function isBlocked(Request $request, $id)
    {
        $currentUser = $request->user(); 

        $isBlocked = Block::where(function ($query) use ($currentUser, $id) {
                            $query->where('blocker_id', $currentUser->id)
                                ->where('blocked_id', $id);
                        })
                        ->orWhere(function ($query) use ($currentUser, $id) {
                            $query->where('blocker_id', $id)
                                ->where('blocked_id', $currentUser->id);
                        })
                        ->exists();

        return response()->json([
            'blocked' => $isBlocked
        ]);
    }

        // API Lấy danh sách theo dõi
    public function followedUsers(Request $request)
    {
        $userId = auth()->id();

        $users = User::whereIn('id', function ($query) use ($userId) {
            $query->select('following_id')
                ->from('follows')
                ->where('follower_id', $userId);
        })->get(['id', 'name', 'avatar']);

        return response()->json([
            'users' => $users
        ]);
    }
    public function blockedUsers(Request $request)
    {
        $userId = auth()->id();

        $users = User::whereIn('id', function ($query) use ($userId) {
            $query->select('blocked_id')
                ->from('blocks')
                ->where('blocker_id', $userId);
        })->get(['id', 'name', 'avatar']);

        return response()->json([
            'users' => $users
        ]);
    }

        public function getFollowStats($userId)
    {
        // Số người đang follow user
        $followersCount = Follow::where('following_id', $userId)->count();

        // Số người user đang follow
        $followingCount = Follow::where('follower_id', $userId)->count();

        return response()->json([
            'followers_count' => $followersCount,
            'following_count' => $followingCount,
        ]);
    }
        public function isMutualFollow(Request $request, $id)
    {
        $currentUser = $request->user();

        // Kiểm tra current user có follow người kia không
        $isFollowing = Follow::where('follower_id', $currentUser->id)
                            ->where('following_id', $id)
                            ->exists();

        // Kiểm tra người kia có follow current user không
        $isFollowedBy = Follow::where('follower_id', $id)
                            ->where('following_id', $currentUser->id)
                            ->exists();

        return response()->json([
            'is_mutual' => $isFollowing && $isFollowedBy,
            'is_following' => $isFollowing,
            'is_followed_by' => $isFollowedBy
        ]);
    }
}
