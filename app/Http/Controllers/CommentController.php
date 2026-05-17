<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function getChapterComments($chapterId)
    {
        $comments = Comment::forChapter($chapterId)
            ->parents()
            ->with(['replies.user', 'user'])
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    public function getEpisodeComments($episodeId)
    {
        $comments = Comment::forEpisode($episodeId)
            ->parents()
            ->with(['replies.user', 'user'])
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|in:chapter,episode',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        if ($request->parent_id) {
            $parent = Comment::find($request->parent_id);
            if ($parent && $parent->parent_id !== null) {
                return response()->json([
                    'message' => 'Parent ID phải là comment gốc, không phải reply.'
                ], 400);
            }
        }

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $request->commentable_id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        $comment->load('user', 'replies.user');

        return response()->json([
            'success' => true,
            'comment' => $comment,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'comment' => $comment,
        ]);
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bình luận',
        ]);
    }

    public function getCommentsCount(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|in:chapter,episode',
            'commentable_id' => 'required|integer',
        ]);

        $count = Comment::where('commentable_type', $request->commentable_type)
            ->where('commentable_id', $request->commentable_id)
            ->count();

        return response()->json([
            'count' => $count,
        ]);
    }
}