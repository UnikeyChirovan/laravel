<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller {
    public function createOrUpdateVote(Request $request) {
        try {
            $user = JWTAuth::parseToken()->authenticate(); 
        } catch (\Exception $e) {
            return response()->json(['message' => 'Bạn phải đăng nhập để vote'], 403);
        }
        $request->validate([
            'choice' => 'required|in:1,2,3'
        ]);

        DB::beginTransaction();
        try {
            $lastVote = Vote::where('user_id', $user->id)->latest()->first();

            if ($lastVote) {
                $now = Carbon::now();
                $votedAt = Carbon::parse($lastVote->voted_at);
                if ($votedAt->diffInDays($now) < 7) {
                    DB::rollBack(); 
                    return response()->json(['message' => 'Bạn chỉ có thể thay đổi vote sau 7 ngày'], 409); // Sử dụng mã 409 cho "Conflict"
                }
                $lastVote->update([
                    'choice' => $request->choice,
                    'voted_at' => Carbon::now(),
                ]);

                DB::commit();
                return response()->json(['message' => 'Thay đổi vote thành công', 'vote' => $lastVote], 200);
            } else {
                $vote = Vote::create([
                    'user_id' => $user->id,
                    'choice' => $request->choice,
                    'voted_at' => Carbon::now(),
                ]);
                DB::commit(); 
                return response()->json(['message' => 'Vote thành công', 'vote' => $vote], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json(['message' => 'Đã xảy ra lỗi, vui lòng thử lại'], 500);
        }
    }
    public function getUserVote(Request $request) {
        try {
            $user = JWTAuth::parseToken()->authenticate(); 
        } catch (\Exception $e) {
            return response()->json(['message' => 'Bạn chưa đăng nhập. Không thể lấy kết quả vote.'], 402);
        }
        $vote = Vote::where('user_id', $user->id)->latest()->first();

        if (!$vote) {
            return response()->json(['message' => 'Bạn chưa thực hiện vote'], 404);
        }
        return response()->json(['vote' => $vote], 200);
    }
    public function getVoteResults() {
        $results = Vote::select('choice', DB::raw('count(*) as total'))
                    ->groupBy('choice')
                    ->get();
        $totalVotes = Vote::distinct('user_id')->count('user_id');
        $formattedResults = [
        'total_users_voted' => $totalVotes,
        'votes_by_choice' => $results
        ];
        return response()->json($formattedResults, 200);
    }
}
