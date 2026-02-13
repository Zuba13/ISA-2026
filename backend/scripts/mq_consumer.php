<?php

// Standalone Consumer Simulation
require __DIR__ . '/../vendor/autoload.php';

echo "MQ Consumer started...\n";

// Function to simulate receiving from queue
function consume($type) {
    echo "Consuming {$type} messages...\n";
    // Mocking 50 messages
    for ($i = 0; $i < 50; $i++) {
        $msg = ($type === 'json') 
            ? json_encode(['title' => 'Test', 'size' => 1234, 'author' => 'User'])
            : pack('VVa*a*', 1234, 4, 'Test', 'User');
        
        $start = microtime(true);
        if ($type === 'json') {
            $data = json_decode($msg, true);
        } else {
            $data = unpack('Vsize/Vtitle_len/a*title/a*author', $msg);
        }
        $time = microtime(true) - $start;
        // echo "Processed {$type} message in " . number_format($time, 8) . "s\n";
    }
}

consume('json');
consume('protobuf');
echo "Finished processing 50 messages for each format.\n";
