<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [
    'status' => 'checking',
    'passport_keys' => [
        'private_key' => false,
        'public_key' => false,
        'private_readable' => false,
        'public_readable' => false
    ],
    'storage_path' => realpath('../storage'),
    'errors' => []
];

// Check if keys exist
$privateKeyPath = '../storage/oauth-private.key';
$publicKeyPath = '../storage/oauth-public.key';

if (file_exists($privateKeyPath)) {
    $response['passport_keys']['private_key'] = true;
    $response['passport_keys']['private_readable'] = is_readable($privateKeyPath);
    if (!$response['passport_keys']['private_readable']) {
        $response['errors'][] = 'Private key exists but is not readable';
    }
} else {
    $response['errors'][] = 'Private key not found at: ' . realpath($privateKeyPath);
}

if (file_exists($publicKeyPath)) {
    $response['passport_keys']['public_key'] = true;
    $response['passport_keys']['public_readable'] = is_readable($publicKeyPath);
    if (!$response['passport_keys']['public_readable']) {
        $response['errors'][] = 'Public key exists but is not readable';
    }
} else {
    $response['errors'][] = 'Public key not found at: ' . realpath($publicKeyPath);
}

// Check permissions
if (file_exists($privateKeyPath)) {
    $response['passport_keys']['private_permissions'] = substr(sprintf('%o', fileperms($privateKeyPath)), -4);
}
if (file_exists($publicKeyPath)) {
    $response['passport_keys']['public_permissions'] = substr(sprintf('%o', fileperms($publicKeyPath)), -4);
}

// Overall status
if ($response['passport_keys']['private_key'] && $response['passport_keys']['public_key'] &&
    $response['passport_keys']['private_readable'] && $response['passport_keys']['public_readable']) {
    $response['status'] = 'configured';
    $response['message'] = 'Passport keys are properly configured';
} else {
    $response['status'] = 'not_configured';
    $response['message'] = 'Passport keys need to be generated. Run: php artisan passport:keys';
}

echo json_encode($response, JSON_PRETTY_PRINT);