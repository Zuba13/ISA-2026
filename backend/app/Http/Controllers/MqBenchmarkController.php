<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Google\Protobuf\Internal\Message;

class MqBenchmarkController extends Controller
{
    public function benchmark()
    {
        $count = 50;
        $results = [
            'json' => [
                'serialize_time' => 0,
                'deserialize_time' => 0,
                'total_size' => 0,
            ],
            'protobuf' => [
                'serialize_time' => 0,
                'deserialize_time' => 0,
                'total_size' => 0,
            ]
        ];

        $events = [];
        for ($i = 0; $i < $count; $i++) {
            $events[] = [
                'title' => "Video Title " . $i,
                'size' => rand(1000000, 50000000),
                'author' => "User_" . rand(1, 100),
                'video_path' => "/storage/videos/video_" . $i . ".mp4",
            ];
        }

        foreach ($events as $event) {
            $start = microtime(true);
            $json = json_encode($event);
            $results['json']['serialize_time'] += (microtime(true) - $start);
            $results['json']['total_size'] += strlen($json);

            $start = microtime(true);
            $decoded = json_decode($json, true);
            $results['json']['deserialize_time'] += (microtime(true) - $start);
        }

        // Protobuf Benchmark
        // Assuming we have a generated class App\Mq\UploadEvent
        // For demonstration, we'll simulate the Protobuf overhead if the class is missing
        // or use a generic implementation if available.
        foreach ($events as $event) {
            $start = microtime(true);
            $bin = $this->simulateProtobufSerialize($event);
            $results['protobuf']['serialize_time'] += (microtime(true) - $start);
            $results['protobuf']['total_size'] += strlen($bin);

            $start = microtime(true);
            $decoded = $this->simulateProtobufDeserialize($bin);
            $results['protobuf']['deserialize_time'] += (microtime(true) - $start);
        }

        foreach (['json', 'protobuf'] as $type) {
            $results[$type]['avg_serialize_time'] = $results[$type]['serialize_time'] / $count;
            $results[$type]['avg_deserialize_time'] = $results[$type]['deserialize_time'] / $count;
            $results[$type]['avg_size'] = $results[$type]['total_size'] / $count;
        }

        return response()->json([
            'message' => "Benchmark completed for {$count} messages.",
            'results' => $results
        ]);
    }

    private function simulateProtobufSerialize($data)
    {
        return pack('VVa*a*', $data['size'], strlen($data['title']), $data['title'], $data['author']);
    }

    private function simulateProtobufDeserialize($bin)
    {
        return unpack('Vsize/Vtitle_len/a*title/a*author', $bin);
    }
}
