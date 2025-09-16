<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

header('Content-Type: application/json');

$required = [
    'APP_ENV' => env('APP_ENV'),
    'APP_KEY' => env('APP_KEY') ? 'SET' : 'MISSING',
    'APP_DEBUG' => env('APP_DEBUG'),
    'APP_URL' => env('APP_URL'),
    'DB_CONNECTION' => env('DB_CONNECTION'),
    'DB_HOST' => env('DB_HOST'),
    'DB_PORT' => env('DB_PORT'),
    'DB_DATABASE' => env('DB_DATABASE'),
    'DB_USERNAME' => env('DB_USERNAME') ? 'SET' : 'MISSING',
    'DB_PASSWORD' => env('DB_PASSWORD') ? 'SET' : 'MISSING',
];

$issues = [];

// Check required values
if (!env('APP_KEY')) {
    $issues[] = 'APP_KEY is not set - run: php artisan key:generate';
}

if (!env('DB_CONNECTION')) {
    $issues[] = 'DB_CONNECTION is not set - should be "pgsql" for PostgreSQL';
}

if (!env('DB_DATABASE')) {
    $issues[] = 'DB_DATABASE is not set - specify your database name';
}

if (!env('DB_PORT')) {
    $issues[] = 'DB_PORT is not set - should be 5432 for PostgreSQL';
}

// Test actual database connection
$dbStatus = 'Not tested';
$dbError = null;

if (env('DB_CONNECTION') && env('DB_HOST') && env('DB_DATABASE')) {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'Connected successfully';
        
        // Check if migrations have been run
        try {
            $migrationCount = DB::table('migrations')->count();
            $dbStatus .= " ($migrationCount migrations found)";
        } catch (\Exception $e) {
            $dbStatus .= ' (migrations table not found - run: php artisan migrate)';
        }
    } catch (\Exception $e) {
        $dbStatus = 'Connection failed';
        $dbError = $e->getMessage();
        
        // Provide helpful suggestions based on error
        if (strpos($dbError, 'Connection refused') !== false) {
            if (env('DB_HOST') === 'postgres') {
                $issues[] = 'Database connection refused. In Docker, ensure postgres container is running.';
            } else {
                $issues[] = 'Database connection refused. Check if PostgreSQL is running on ' . env('DB_HOST') . ':' . env('DB_PORT', 5432);
            }
        } elseif (strpos($dbError, 'database') !== false && strpos($dbError, 'does not exist') !== false) {
            $issues[] = 'Database "' . env('DB_DATABASE') . '" does not exist. Create it with: createdb ' . env('DB_DATABASE');
        } elseif (strpos($dbError, 'password authentication failed') !== false) {
            $issues[] = 'Database password is incorrect for user ' . env('DB_USERNAME');
        }
    }
}

$result = [
    'status' => empty($issues) && $dbStatus === 'Connected successfully' ? 'OK' : 'Issues Found',
    'environment' => env('APP_ENV', 'not set'),
    'required_vars' => $required,
    'database' => [
        'status' => $dbStatus,
        'error' => $dbError
    ],
    'issues' => $issues,
    'suggestions' => [
        'If using Docker' => 'Ensure DB_HOST=postgres and the postgres container is running',
        'If using local PostgreSQL' => 'Set DB_HOST=127.0.0.1 or localhost',
        'To fix missing variables' => 'Copy .env.production.example to .env and fill in the values'
    ]
];

echo json_encode($result, JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);