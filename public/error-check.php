<?php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "=== Laravel Error Check ===\n\n";

// Check if vendor exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("ERROR: Vendor directory not found. Run: composer install\n");
}

require __DIR__ . '/../vendor/autoload.php';

try {
    echo "1. Loading Laravel application...\n";
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "   ✓ Application loaded\n\n";
    
    echo "2. Making kernel...\n";
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "   ✓ Kernel created\n\n";
    
    echo "3. Checking environment...\n";
    echo "   APP_ENV: " . env('APP_ENV', 'not set') . "\n";
    echo "   APP_DEBUG: " . (env('APP_DEBUG', false) ? 'true' : 'false') . "\n";
    echo "   APP_KEY: " . (env('APP_KEY') ? 'SET' : 'NOT SET') . "\n\n";
    
    echo "4. Checking storage directories...\n";
    $dirs = [
        'storage/framework/sessions',
        'storage/framework/views', 
        'storage/framework/cache',
        'storage/logs',
        'bootstrap/cache'
    ];
    
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/../' . $dir;
        if (is_dir($path)) {
            echo "   ✓ $dir exists";
            echo is_writable($path) ? " (writable)\n" : " (NOT WRITABLE!)\n";
        } else {
            echo "   ✗ $dir MISSING!\n";
        }
    }
    echo "\n";
    
    echo "5. Checking database connection...\n";
    try {
        $pdo = DB::connection()->getPdo();
        echo "   ✓ Database connected\n";
        
        // Check users table
        $users = DB::table('users')->count();
        echo "   ✓ Users table accessible ($users users)\n";
        
        // Check admin users
        $admins = DB::table('users')->where('is_admin', true)->count();
        echo "   ✓ Admin users: $admins\n";
    } catch (Exception $e) {
        echo "   ✗ Database error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "6. Testing admin login view...\n";
    if (View::exists('admin.login')) {
        echo "   ✓ Admin login view exists\n";
        
        // Try to render it
        try {
            $view = View::make('admin.login');
            echo "   ✓ View can be rendered\n";
        } catch (Exception $e) {
            echo "   ✗ View rendering error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✗ Admin login view NOT FOUND\n";
    }
    echo "\n";
    
    echo "7. Checking session configuration...\n";
    echo "   SESSION_DRIVER: " . env('SESSION_DRIVER', 'not set') . "\n";
    echo "   SESSION_LIFETIME: " . env('SESSION_LIFETIME', 'not set') . "\n";
    
    // Check if session directory is writable
    if (env('SESSION_DRIVER') === 'file') {
        $sessionPath = __DIR__ . '/../storage/framework/sessions';
        if (is_writable($sessionPath)) {
            echo "   ✓ Session directory is writable\n";
        } else {
            echo "   ✗ Session directory NOT WRITABLE!\n";
        }
    }
    echo "\n";
    
    echo "8. Checking routes...\n";
    $routes = Route::getRoutes();
    $adminRoutes = [];
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'admin') !== false) {
            $adminRoutes[] = $route->methods()[0] . ' ' . $route->uri();
        }
    }
    echo "   Found " . count($adminRoutes) . " admin routes\n";
    foreach (array_slice($adminRoutes, 0, 5) as $route) {
        echo "   - $route\n";
    }
    echo "\n";
    
    echo "9. Checking Laravel log file...\n";
    $logFile = __DIR__ . '/../storage/logs/laravel.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $lastError = null;
        foreach (array_reverse($lines) as $line) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                $lastError = $line;
                break;
            }
        }
        if ($lastError) {
            echo "   Last error: " . substr($lastError, 0, 200) . "...\n";
        } else {
            echo "   ✓ No recent errors in log\n";
        }
    } else {
        echo "   Log file not found\n";
    }
    echo "\n";
    
    echo "=== Check Complete ===\n";
    echo "If everything above is ✓, the application should work.\n";
    echo "Fix any ✗ items to resolve 500 errors.\n";
    
} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}