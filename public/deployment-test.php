<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'success',
    'message' => 'Deployment test - ' . date('Y-m-d H:i:s'),
    'deployment_id' => uniqid('deploy_'),
    'cors_enabled' => true,
    'version' => '1.0.1',
], JSON_PRETTY_PRINT);