<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Video;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    /**
     * Toggle like on a video or comment
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'likeable_type' => 'required|string|in:App\Models\Video,App\Models\Comment',
            'likeable_id' => 'required|integer|exists:' . $this->getTableName($request->likeable_type) . ',id',
        ]);

        $userId = Auth::id();
        $likeableType = $request->likeable_type;
        $likeableId = $request->likeable_id;

        // Check if like already exists
        $existingLike = Like::where('user_id', $userId)
            ->where('likeable_type', $likeableType)
            ->where('likeable_id', $likeableId)
            ->first();

        if ($existingLike) {
            // Unlike
            $existingLike->delete();
            return response()->json([
                'message' => 'Like removed',
                'liked' => false
            ]);
        } else {
            // Like
            $like = Like::create([
                'user_id' => $userId,
                'likeable_type' => $likeableType,
                'likeable_id' => $likeableId,
            ]);

            return response()->json([
                'message' => 'Like added',
                'liked' => true,
                'like' => $like
            ], 201);
        }
    }

    /**
     * Get all likes for a video or comment
     */
    public function index($likeableType, $likeableId): JsonResponse
    {
        // Map the type parameter to the full class name
        $typeMap = [
            'video' => 'App\Models\Video',
            'comment' => 'App\Models\Comment',
        ];

        if (!isset($typeMap[$likeableType])) {
            return response()->json(['message' => 'Invalid likeable type'], 400);
        }

        $fullType = $typeMap[$likeableType];

        $likes = Like::where('likeable_type', $fullType)
            ->where('likeable_id', $likeableId)
            ->with('user:id,username,name,surname')
            ->get();

        return response()->json([
            'count' => $likes->count(),
            'likes' => $likes
        ]);
    }

    /**
     * Helper method to get table name from model class
     */
    private function getTableName($modelClass): string
    {
        return match($modelClass) {
            'App\Models\Video' => 'videos',
            'App\Models\Comment' => 'comments',
            default => '',
        };
    }
}
