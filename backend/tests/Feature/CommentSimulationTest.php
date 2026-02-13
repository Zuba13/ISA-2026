<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CommentSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_commenting_rate_limit()
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $this->actingAs($user);

        // Send 60 comments
        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson("/api/videos/{$video->id}/comments", [
                'content' => "Comment {$i}"
            ]);
            $response->assertStatus(201);
        }

        // The 61st comment should be rate limited
        $response = $this->postJson("/api/videos/{$video->id}/comments", [
            'content' => "Comment 61"
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure(['message']);
    }

    public function test_comment_pagination_and_caching()
    {
        Cache::flush();
        $user = User::factory()->create();
        $video = Video::factory()->create();

        // Create 25 comments
        Comment::factory()->count(25)->create([
            'video_id' => $video->id,
            'user_id' => $user->id
        ]);

        // First page (10 comments)
        $response = $this->getJson("/api/videos/{$video->id}/comments?page=1");
        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data.data');
        $this->assertEquals(25, $response->json('data.total'));

        // Verify caching: check if cache key exists
        $cacheKey = "video_{$video->id}_comments_page_1";
        $this->assertTrue(Cache::has($cacheKey));

        // Adding a new comment should invalidate cache for page 1
        $this->actingAs($user);
        $this->postJson("/api/videos/{$video->id}/comments", [
            'content' => "New comment"
        ]);

        $this->assertFalse(Cache::has($cacheKey));
    }
}
