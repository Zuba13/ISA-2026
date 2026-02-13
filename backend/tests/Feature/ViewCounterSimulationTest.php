<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewCounterSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_counter_increments_atomically()
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['views' => 0]);

        // Simulate 50 visits
        for ($i = 0; $i < 50; $i++) {
            $response = $this->getJson("/api/videos/{$video->id}");
            $response->assertStatus(200);
        }

        $video->refresh();
        $this->assertEquals(50, $video->views);
    }
}
