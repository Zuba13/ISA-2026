<?php

/**
 * SIMULATED CONSUMER APPLICATION
 * 
 * This script represents a secondary application that listens for 
 * VideoUpload events from the main Jutjubić application.
 */

class VideoUploadConsumer
{
    public function receiveMessage($payload, $format = 'json')
    {
        echo "[Consumer] Received new message in {$format} format.\n";

        if ($format === 'json') {
            $data = json_decode($payload, true);
        } else {
            // Simulated Protobuf decoding
            $data = $this->decodeProto($payload);
        }

        echo "[Consumer] Processing video: " . $data['title'] . " by " . $data['author'] . "\n";
        echo "[Consumer] File size: " . round($data['size'] / 1024 / 1024, 2) . " MB\n";
    }

    private function decodeProto($payload)
    {
        // Mocking the field extraction from binary
        // Tag 1 (title), Tag 2 (size), Tag 3 (author)
        return [
            'title' => 'Simulated Title',
            'size' => 12345678,
            'author' => 'Author Mock',
        ];
    }
}

// Example usage
$consumer = new VideoUploadConsumer();
$sampleJson = json_encode(['title' => 'Sample Video', 'size' => 5000000, 'author' => 'AI User']);
$consumer->receiveMessage($sampleJson, 'json');
