<?php

header('Content-Type: application/json');

// Get environment variables
$db_host = getenv('DB_HOST') ?: 'not set';
$db_port = getenv('DB_PORT') ?: 'not set';
$db_database = getenv('DB_DATABASE') ?: 'not set';
$db_username = getenv('DB_USERNAME') ?: 'not set';
$db_password = getenv('DB_PASSWORD') ? '***SET***' : 'not set';

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment_vars' => [
        'DB_HOST' => $db_host,
        'DB_PORT' => $db_port,
        'DB_DATABASE' => $db_database,
        'DB_USERNAME' => $db_username,
        'DB_PASSWORD' => $db_password
    ],
    'dotenv_file' => []
];

// Check .env file values
if (file_exists(__DIR__ . '/../.env')) {
    $env_contents = file_get_contents(__DIR__ . '/../.env');
    preg_match('/^DB_HOST=(.*)$/m', $env_contents, $host_match);
    preg_match('/^DB_PORT=(.*)$/m', $env_contents, $port_match);
    preg_match('/^DB_DATABASE=(.*)$/m', $env_contents, $db_match);
    preg_match('/^DB_USERNAME=(.*)$/m', $env_contents, $user_match);
    preg_match('/^DB_PASSWORD=(.*)$/m', $env_contents, $pass_match);
    
    $result['dotenv_file'] = [
        'DB_HOST' => $host_match[1] ?? 'not found',
        'DB_PORT' => $port_match[1] ?? 'not found',
        'DB_DATABASE' => $db_match[1] ?? 'not found',
        'DB_USERNAME' => $user_match[1] ?? 'not found',
        'DB_PASSWORD' => isset($pass_match[1]) && $pass_match[1] ? '***SET***' : 'not found'
    ];
}

// Test connection with environment variables
if ($db_host !== 'not set' && $db_database !== 'not set') {
    try {
        $dsn = "pgsql:host=$db_host;port=" . ($db_port ?: '5432') . ";dbname=$db_database";
        $pdo = new PDO($dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
        $result['env_connection_test'] = 'SUCCESS';
    } catch (Exception $e) {
        $result['env_connection_test'] = 'FAILED';
        $result['env_error'] = $e->getMessage();
    }
}

// Test connection with .env file values
if (isset($host_match[1]) && isset($db_match[1]) && isset($user_match[1]) && isset($pass_match[1])) {
    try {
        $dsn = "pgsql:host={$host_match[1]};port=" . ($port_match[1] ?? '5432') . ";dbname={$db_match[1]}";
        $pdo = new PDO($dsn, $user_match[1], $pass_match[1]);
        $result['dotenv_connection_test'] = 'SUCCESS';
    } catch (Exception $e) {
        $result['dotenv_connection_test'] = 'FAILED';
        $result['dotenv_error'] = $e->getMessage();
    }
}

// Check if credentials match
$result['credentials_match'] = (
    $result['environment_vars']['DB_HOST'] === $result['dotenv_file']['DB_HOST'] &&
    $result['environment_vars']['DB_PORT'] === $result['dotenv_file']['DB_PORT'] &&
    $result['environment_vars']['DB_DATABASE'] === $result['dotenv_file']['DB_DATABASE'] &&
    $result['environment_vars']['DB_USERNAME'] === $result['dotenv_file']['DB_USERNAME']
) ? 'YES' : 'NO';

// Provide diagnosis
$result['diagnosis'] = [];
if ($result['credentials_match'] === 'NO') {
    $result['diagnosis'][] = 'Environment variables and .env file have different values';
}
if ($db_username === 'not set') {
    $result['diagnosis'][] = 'DB_USERNAME environment variable is not set';
}
if ($db_password === 'not set') {
    $result['diagnosis'][] = 'DB_PASSWORD environment variable is not set';
}

echo json_encode($result, JSON_PRETTY_PRINT);