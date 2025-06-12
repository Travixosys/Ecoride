<?php
// scripts/test-mongo.php

require __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

$uri = getenv('MONGO_URI');
if (! $uri) {
    fwrite(STDERR, "Error: MONGO_URI is not set in the environment.\n");
    exit(1);
}

$client = new Client($uri);

try {
    $result = $client
        ->selectDatabase('admin')
        ->command(['ping' => 1])
        ->toArray()[0];

    echo "✅ Successfully connected and pinged MongoDB Atlas! Response:\n";
    print_r($result);
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "❌ Connection failed:\n" . $e->getMessage() . "\n");
    exit(1);
}
