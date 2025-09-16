<?php

header('Content-Type: application/json');

echo json_encode([
    'status' => 'OK',
    'message' => 'Test deployment successful!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_time' => time(),
    'php_version' => PHP_VERSION,
    'environment' => [
        'APP_ENV' => $_ENV['APP_ENV'] ?? 'not set',
        'DB_HOST' => $_ENV['DB_HOST'] ?? 'not set',
    ]
], JSON_PRETTY_PRINT);