<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'success',
    'message' => 'Deployment test for logs dashboard - ' . date('Y-m-d H:i:s'),
    'deployment_id' => uniqid('logs_deploy_'),
    'features' => [
        'logs_dashboard' => true,
        'log_filtering' => true,
        'log_search' => true,
        'log_clearing' => true,
        'pagination' => true
    ],
    'cors_enabled' => true,
    'version' => '1.0.2',
], JSON_PRETTY_PRINT);