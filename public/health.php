<?php

header('Content-Type: application/json');

$response = [
    'status' => 'checking',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check if Laravel bootstrap file exists
$response['checks']['bootstrap'] = file_exists(__DIR__ . '/../vendor/autoload.php') ? 'OK' : 'FAIL';

// Check .env file
$response['checks']['env_file'] = file_exists(__DIR__ . '/../.env') ? 'OK' : 'MISSING';

// Check view directory
$response['checks']['views_dir'] = is_dir(__DIR__ . '/../resources/views/admin') ? 'OK' : 'MISSING';

// Check admin login view
$response['checks']['admin_login_view'] = file_exists(__DIR__ . '/../resources/views/admin/login.blade.php') ? 'OK' : 'MISSING';

// Try to check database via env
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $response['checks']['db_connection'] = $env['DB_CONNECTION'] ?? 'NOT_SET';
    $response['checks']['db_host'] = $env['DB_HOST'] ?? 'NOT_SET';
    $response['checks']['db_database'] = $env['DB_DATABASE'] ?? 'NOT_SET';
    $response['checks']['app_env'] = $env['APP_ENV'] ?? 'NOT_SET';
    $response['checks']['app_debug'] = $env['APP_DEBUG'] ?? 'NOT_SET';
}

// Try to connect to database if we have credentials
if (isset($env)) {
    try {
        $dsn = sprintf(
            "%s:host=%s;port=%s;dbname=%s",
            $env['DB_CONNECTION'] ?? 'mysql',
            $env['DB_HOST'] ?? '127.0.0.1',
            $env['DB_PORT'] ?? '3306',
            $env['DB_DATABASE'] ?? ''
        );
        
        $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '');
        $response['checks']['database_connection'] = 'OK';
    } catch (Exception $e) {
        $response['checks']['database_connection'] = 'FAIL';
        $response['checks']['database_error'] = $e->getMessage();
    }
}

$hasErrors = false;
foreach ($response['checks'] as $check => $value) {
    if (in_array($value, ['FAIL', 'MISSING', 'NOT_SET']) || strpos($value, 'FAIL') === 0) {
        $hasErrors = true;
        break;
    }
}

$response['status'] = $hasErrors ? 'error' : 'operational';

echo json_encode($response, JSON_PRETTY_PRINT);