<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test different error scenarios
$tests = [
    [
        'name' => 'Configuration Error Test',
        'description' => 'Tests that configuration errors return 400 instead of 500',
        'endpoints' => [
            '/api/google/login' => 'Google OAuth (should return 400 if not configured)',
            '/api/config/status' => 'Configuration status (should always work)',
            '/api/config/validate' => 'Configuration validation (should return 400 if issues)',
            '/api/config/status/google' => 'Google service status',
            '/api/config/status/quickbooks' => 'QuickBooks service status'
        ]
    ],
    [
        'name' => 'Authentication Error Test',
        'description' => 'Tests that auth errors return 401',
        'endpoints' => [
            '/api/account' => 'Account endpoint (should return 401 without auth)',
            '/api/loads' => 'Loads endpoint (should return 401 without auth)'
        ]
    ],
    [
        'name' => 'Not Found Error Test',
        'description' => 'Tests that missing endpoints return 404',
        'endpoints' => [
            '/api/nonexistent' => 'Non-existent endpoint (should return 404)',
            '/api/loads/999999' => 'Non-existent load (should return 404)'
        ]
    ]
];

echo json_encode([
    'status' => 'ready',
    'message' => 'Error handling tests configured',
    'deployment_date' => date('Y-m-d H:i:s'),
    'tests_available' => $tests,
    'instructions' => 'Access the endpoints listed to test error handling improvements',
    'expected_behavior' => [
        'configuration_errors' => 'Should return HTTP 400 (Bad Request)',
        'authentication_errors' => 'Should return HTTP 401 (Unauthorized)',
        'not_found_errors' => 'Should return HTTP 404 (Not Found)',
        'validation_errors' => 'Should return HTTP 422 (Unprocessable Entity)',
        'server_errors' => 'Should return HTTP 500 only for actual server crashes'
    ]
], JSON_PRETTY_PRINT);