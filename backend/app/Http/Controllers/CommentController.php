<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class CommentController extends Controller
{
    /**
     * Get all comments for a specific video
     */
    public function index($videoId): JsonResponse
    {
        $page = request()->get('page', 1);
        $cacheKey = "video_{$videoId}_comments_page_{$page}";

        $comments = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($videoId) {
            return Comment::where('video_id', $videoId)
                ->with(['user:id,username,name,surname', 'likes'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        });

        return response()->json($comments);
    }

    /**
     * Store a new comment
     */
    public function store(Request $request, $videoId): JsonResponse
    {
        $userId = Auth::id();
        
        // Rate limiting: 60 comments per hour per user
        $oneHourAgo = now()->subHour();
        $commentCount = Comment::where('user_id', $userId)
            ->where('created_at', '>=', $oneHourAgo)
            ->count();

        if ($commentCount >= 60) {
            return response()->json([
                'message' => 'You have reached the limit of 60 comments per hour.'
            ], 429);
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'user_id' => $userId,
            'video_id' => $videoId,
            'content' => $request->content,
        ]);

        // Invalidate cache for this video's comments
        \Illuminate\Support\Facades\Cache::forget("video_{$videoId}_comments_page_1");

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

        // Invalidate cache for this video's comments
        \Illuminate\Support\Facades\Cache::forget("video_{$comment->video_id}_comments_page_1");

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

        $videoId = $comment->video_id;
        $comment->delete();

        // Invalidate cache for this video's comments
        \Illuminate\Support\Facades\Cache::forget("video_{$videoId}_comments_page_1");

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
