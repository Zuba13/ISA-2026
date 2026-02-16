<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoSynced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $videoId;
    public $currentTime;
    public $isPlaying;
    public $senderId;

    public function __construct($roomId, $videoId, $currentTime, $isPlaying, $senderId)
    {
        $this->roomId = $roomId;
        $this->videoId = $videoId;
        $this->currentTime = $currentTime;
        $this->isPlaying = $isPlaying;
        $this->senderId = $senderId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.' . $this->roomId),
        ];
    }

    public function broadcastAs()
    {
        return 'video.synced';
    }
}
