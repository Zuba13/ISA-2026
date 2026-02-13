<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function index(): JsonResponse
    {
        $videos = Video::with(['user:id,username,name,surname', 'likes', 'comments.user:id,username,name,surname'])
            ->withCount('comments')
            ->latest()
            ->get();
        return response()->json($videos);
    }

    public function show($id): JsonResponse
    {
        $video = Video::with(['user:id,username,name,surname', 'likes'])
            ->withCount('comments')
            ->findOrFail($id);

        // Atomic increment of views
        $video->increment('views');

        return response()->json($video);
    }
}
