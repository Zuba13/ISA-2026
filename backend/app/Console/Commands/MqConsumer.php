<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class MqConsumer extends Command
{
    protected $signature = 'mq:consume';
    protected $description = 'Consume UploadEvent messages from the queue';

    public function handle()
    {
        $this->info("MQ Consumer started. Listening for UploadEvents...");

        while (true) {
            // Simulate listening to a queue
            $message = Redis::lpop('video_uploads');
            
            if ($message) {
                $event = json_decode($message, true);
                $this->info("--------------------------------");
                $this->info("RECEIVED NEW VIDEO NOTIFICATION");
                $this->info("Title: " . $event['title']);
                $this->info("Size: " . round($event['size'] / 1024 / 1024, 2) . " MB");
                $this->info("Author: " . $event['author']);
                $this->info("Format: " . ($event['format'] ?? 'JSON'));
                $this->info("--------------------------------");
            }
            
            usleep(500000); // Poll every 0.5s
        }
    }
}
