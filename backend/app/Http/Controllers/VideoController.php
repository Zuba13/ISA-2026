<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function stream($id)
    {
        $video = Video::findOrFail($id);
        $path = $video->video_path;

        // If path starts with /storage/, strip it to get the public disk path
        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, strlen('/storage/'));
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Video file not found');
        }

        $filePath = Storage::disk('public')->path($path);
        $mimeType = mime_content_type($filePath) ?: 'video/mp4';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
        ]);
    }
}
