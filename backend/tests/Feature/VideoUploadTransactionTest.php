<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VideoUploadTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_upload_transactional_rollback_on_failure()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);

        // Simulate a failure by triggering the 'simulate_timeout' flag we added to the controller
        $videoFile = UploadedFile::fake()->create('video.mp4', 500); // 500KB
        $thumbnailFile = UploadedFile::fake()->image('thumbnail.jpg');

        $response = $this->postJson('/api/videos', [
            'title' => 'Test Video',
            'description' => 'Test Description',
            'tags' => ['test', 'rollback'],
            'location' => 'Novi Sad',
            'video' => $videoFile,
            'thumbnail' => $thumbnailFile,
            'simulate_timeout' => true // This triggers an exception in our controller
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseCount('videos', 0);
        
        // Verify files were deleted
        Storage::disk('public')->assertMissing('videos/' . $videoFile->hashName());
        Storage::disk('public')->assertMissing('thumbnails/' . $thumbnailFile->hashName());
    }

    public function test_successful_video_upload_with_metadata_and_cache()
    {
        Storage::fake('public');
        Cache::flush();
        $user = User::factory()->create();
        $this->actingAs($user);

        $videoFile = UploadedFile::fake()->create('video.mp4', 1000);
        $thumbnailFile = UploadedFile::fake()->image('thumbnail.jpg');

        $response = $this->postJson('/api/videos', [
            'title' => 'Success Video',
            'description' => 'Detailed Description',
            'tags' => ['success', 'tag'],
            'location' => 'Belgrade',
            'video' => $videoFile,
            'thumbnail' => $thumbnailFile,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('videos', [
            'title' => 'Success Video',
            'location' => 'Belgrade'
        ]);

        $video = Video::first();
        $this->assertEquals(['success', 'tag'], $video->tags);

        // Verify files exist
        Storage::disk('public')->assertExists($video->video_path);
        Storage::disk('public')->assertExists($video->thumbnail_path);

        // Verify thumbnail is cached
        $this->assertTrue(Cache::has("thumbnail_{$video->id}"));
    }

    public function test_successful_mov_video_upload()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);

        // 'quicktime' is the extension for .mov, used by UploadedFile::fake()
        $videoFile = UploadedFile::fake()->create('video.mov', 1000, 'video/quicktime');
        $thumbnailFile = UploadedFile::fake()->image('thumbnail.jpg');

        $response = $this->postJson('/api/videos', [
            'title' => 'MOV Video',
            'description' => 'Test MOV upload',
            'video' => $videoFile,
            'thumbnail' => $thumbnailFile,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('videos', [
            'title' => 'MOV Video',
        ]);
        
        $video = Video::where('title', 'MOV Video')->first();
        Storage::disk('public')->assertExists($video->video_path);
    }
}
