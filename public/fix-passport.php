<?php

// This file will run the Passport installation commands
// It should be deleted after use

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [
    'status' => 'running',
    'steps' => [],
    'errors' => []
];

// Change to Laravel root
chdir('..');

// Step 1: Run passport:install
$response['steps'][] = 'Running passport:install...';
exec('php artisan passport:install --force 2>&1', $output1, $return1);
$response['passport_install'] = [
    'return_code' => $return1,
    'output' => implode("\n", $output1)
];

// Step 2: Generate keys if needed
$response['steps'][] = 'Generating keys...';
exec('php artisan passport:keys --force 2>&1', $output2, $return2);
$response['passport_keys'] = [
    'return_code' => $return2,
    'output' => implode("\n", $output2)
];

// Step 3: Set permissions
$response['steps'][] = 'Setting permissions...';
if (file_exists('storage/oauth-private.key')) {
    chmod('storage/oauth-private.key', 0600);
    $response['private_key_permissions'] = 'Set to 0600';
}
if (file_exists('storage/oauth-public.key')) {
    chmod('storage/oauth-public.key', 0644);
    $response['public_key_permissions'] = 'Set to 0644';
}

// Step 4: Clear caches
$response['steps'][] = 'Clearing caches...';
exec('php artisan config:clear 2>&1', $output3);
exec('php artisan cache:clear 2>&1', $output4);
$response['cache_clear'] = 'Complete';

// Step 5: Verify keys exist
$response['verification'] = [
    'private_key_exists' => file_exists('storage/oauth-private.key'),
    'public_key_exists' => file_exists('storage/oauth-public.key'),
    'private_key_readable' => is_readable('storage/oauth-private.key'),
    'public_key_readable' => is_readable('storage/oauth-public.key')
];

// Overall status
if ($response['verification']['private_key_exists'] && 
    $response['verification']['public_key_exists']) {
    $response['status'] = 'success';
    $response['message'] = 'Passport has been successfully installed';
} else {
    $response['status'] = 'failed';
    $response['message'] = 'Passport installation may have failed';
}

echo json_encode($response, JSON_PRETTY_PRINT);