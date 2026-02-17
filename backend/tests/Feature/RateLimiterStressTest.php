<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RateLimiterStressTest extends TestCase
{
    /**
     * Stress test the API rate limiter to prove stability under load.
     */
    public function test_api_stability_under_high_load()
    {
        $url = "http://localhost/api/videos";
        $totalRequests = 200;
        $successCount = 0;
        $blockedCount = 0;

        $this->info("Starting Stress Test: Sending $totalRequests requests to $url...");

        $startTime = microtime(true);

        for ($i = 0; $i < $totalRequests; $i++) {
            $response = Http::get($url);
            
            if ($response->status() === 200) {
                $successCount++;
            } elseif ($response->status() === 429) {
                $blockedCount++;
            }
        }

        $duration = microtime(true) - $startTime;

        $this->info("------------------------------------");
        $this->info("STRESS TEST RESULTS");
        $this->info("Total Requests: $totalRequests");
        $this->info("Successful (200 OK): $successCount");
        $this->info("Blocked (429 Too Many Requests): $blockedCount");
        $this->info("Total Duration: " . round($duration, 2) . "s");
        $this->info("Avg Request Time: " . round(($duration / $totalRequests) * 1000, 2) . "ms");
        $this->info("------------------------------------");

        $this->assertGreaterThan(0, $blockedCount, "Rate limiter should have blocked some requests.");
        $this->assertEquals(60, $successCount, "Exactly 60 requests should succeed per minute.");
    }

    private function info($msg)
    {
        echo $msg . "\n";
    }
}
