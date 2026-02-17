<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Exception;

class VideoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tags' => 'nullable|array',
            'location' => 'nullable|string',
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime|max:204800', // 200MB
            'thumbnail' => 'required|image|max:5120', // 5MB
        ]);

        $videoPath = null;
        $thumbnailPath = null;

        DB::beginTransaction();

        try {
            if ($request->hasFile('video')) {
                $videoPath = $request->file('video')->store('videos', 'public');
            }

            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $img = null;
                $extension = strtolower($thumbnail->getClientOriginalExtension());
                
                if ($extension === 'jpg' || $extension === 'jpeg') {
                    $img = @imagecreatefromjpeg($thumbnail->getRealPath());
                } elseif ($extension === 'png') {
                    $img = @imagecreatefrompng($thumbnail->getRealPath());
                } elseif ($extension === 'webp') {
                    $img = @imagecreatefromwebp($thumbnail->getRealPath());
                } elseif ($extension === 'gif') {
                    $img = @imagecreatefromgif($thumbnail->getRealPath());
                }

                if ($img) {
                    ob_start();
                    imagejpeg($img, null, 75); 
                    $compressedImage = ob_get_clean();
                    
                    // Sanitize filename
                    $safeName = preg_replace('/[^A-Za-z0-9]/', '_', pathinfo($thumbnail->getClientOriginalName(), PATHINFO_FILENAME));
                    $thumbnailName = time() . '_' . $safeName . '.jpg';
                    $thumbnailPath = 'thumbnails/' . $thumbnailName;
                    
                    Storage::disk('public')->put($thumbnailPath, $compressedImage);
                    imagedestroy($img);
                } else {
                    $thumbnailPath = $thumbnail->store('thumbnails', 'public');
                }
            }

            // Simulate timeout/failure if requested (for testing purposes)
            if ($request->has('simulate_timeout') && $request->simulate_timeout) {
                throw new Exception("Simulated upload timeout/failure");
            }

            $video = Video::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
                'tags' => $request->tags,
                'location' => $request->location,
                'thumbnail_path' => $thumbnailPath,
                'video_path' => $videoPath,
                'views' => 0
            ]);

            DB::commit();

            // MQ Notification (JSON/Protobuf Benchmark Task 3.14)
            try {
                \Illuminate\Support\Facades\Redis::rpush('video_uploads', json_encode([
                    'title' => $video->title,
                    'size' => $request->file('video')->getSize(),
                    'author' => Auth::user()->username,
                    'timestamp' => now()->toDateTimeString(),
                    'format' => 'JSON'
                ]));
            } catch (\Exception $e) {
                // Don't fail the upload if MQ is down
            }

            // Cache the thumbnail path
            Cache::put("thumbnail_{$video->id}", $thumbnailPath, now()->addHours(24));

            return response()->json($video, 201);

        } catch (Exception $e) {
            DB::rollBack();

            // Delete uploaded files if any
            if ($videoPath) {
                Storage::disk('public')->delete($videoPath);
            }
            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }

            return response()->json([
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

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

    public function thumbnail($id)
    {
        $video = Video::findOrFail($id);
        $cacheKey = "thumbnail_data_{$id}";

        $cachedBase64 = Cache::get($cacheKey);
        $imageData = null;

        if ($cachedBase64) {
            $imageData = base64_decode($cachedBase64);
        } else {
            $path = $video->thumbnail_path;
            if (str_starts_with($path, '/storage/')) {
                $path = substr($path, strlen('/storage/'));
            }

            if (Storage::disk('public')->exists($path)) {
                $imageData = Storage::disk('public')->get($path);
                // Base64 encode to avoid database character encoding issues with binary data
                Cache::put($cacheKey, base64_encode($imageData), now()->addHours(24));
            }
        }

        if (!$imageData) {
            abort(404, 'Thumbnail not found');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);

        return response($imageData)->header('Content-Type', $mimeType);
    }

    // Private methods removed as logic is now inline or unused.
}
