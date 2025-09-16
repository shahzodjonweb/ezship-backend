<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

header('Content-Type: application/json');

$result = [
    'status' => 'checking',
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => [
        'APP_ENV' => env('APP_ENV', 'not set'),
        'APP_DEBUG' => env('APP_DEBUG', false),
        'APP_URL' => env('APP_URL', 'not set'),
    ],
    'database' => [
        'DB_CONNECTION' => env('DB_CONNECTION', 'not set'),
        'DB_HOST' => env('DB_HOST', 'not set'),
        'DB_PORT' => env('DB_PORT', 'not set'),
        'DB_DATABASE' => env('DB_DATABASE', 'not set'),
    ],
    'checks' => []
];

// Test database connection
try {
    DB::connection()->getPdo();
    $result['checks']['database'] = 'Connected';
    $result['checks']['migrations'] = DB::table('migrations')->count() . ' migrations';
} catch (\Exception $e) {
    $result['checks']['database'] = 'Failed';
    $result['checks']['error'] = $e->getMessage();
}

// Check for admin user
try {
    $adminCount = DB::table('users')->where('is_admin', true)->count();
    $result['checks']['admin_users'] = $adminCount . ' admin user(s)';
} catch (\Exception $e) {
    $result['checks']['admin_users'] = 'Could not check';
}

// Check view exists
$result['checks']['admin_login_view'] = View::exists('admin.login') ? 'Exists' : 'Missing';

// Determine overall status
$result['status'] = isset($result['checks']['error']) ? 'error' : 'operational';

echo json_encode($result, JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);