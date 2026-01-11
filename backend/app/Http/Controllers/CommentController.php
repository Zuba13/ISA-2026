<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Get all comments for a specific video
     */
    public function index($videoId): JsonResponse
    {
        $comments = Comment::where('video_id', $videoId)
            ->with(['user:id,username,name,surname', 'likes'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }

    /**
     * Store a new comment
     */
    public function store(Request $request, $videoId): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'video_id' => $videoId,
            'content' => $request->content,
        ]);

        $comment->load(['user:id,username,name,surname', 'likes']);

        return response()->json($comment, 201);
    }

    /**
     * Update a comment
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::findOrFail($id);

        // Check if user owns the comment
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->update([
            'content' => $request->content,
        ]);

        $comment->load(['user:id,username,name,surname', 'likes']);

        return response()->json($comment);
    }

    /**
     * Delete a comment
     */
    public function destroy($id): JsonResponse
    {
        $comment = Comment::findOrFail($id);

        // Check if user owns the comment
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
