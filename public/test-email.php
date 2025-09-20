<?php

// Test email configuration
// DELETE THIS FILE AFTER TESTING

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../vendor/autoload.php';
$app = require_once '../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

$response = [
    'status' => 'testing',
    'config' => [],
    'test_result' => null,
    'errors' => []
];

// Get current mail configuration
$response['config'] = [
    'driver' => config('mail.default'),
    'host' => config('mail.mailers.smtp.host'),
    'port' => config('mail.mailers.smtp.port'),
    'encryption' => config('mail.mailers.smtp.encryption'),
    'username' => config('mail.mailers.smtp.username'),
    'username_masked' => substr(config('mail.mailers.smtp.username'), 0, 3) . '***' . substr(config('mail.mailers.smtp.username'), -10),
    'password_set' => !empty(config('mail.mailers.smtp.password')),
    'from_address' => config('mail.from.address'),
    'from_name' => config('mail.from.name')
];

// Check if email is configured
if (empty(config('mail.mailers.smtp.username')) || config('mail.mailers.smtp.username') === 'your-email@gmail.com') {
    $response['status'] = 'not_configured';
    $response['message'] = 'Email is not configured. Please update MAIL_USERNAME and MAIL_PASSWORD in .env';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// Test email sending (only if 'send' parameter is provided)
if (isset($_GET['send']) && isset($_GET['to'])) {
    $testEmail = filter_var($_GET['to'], FILTER_VALIDATE_EMAIL);
    
    if (!$testEmail) {
        $response['status'] = 'error';
        $response['message'] = 'Invalid email address provided';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    try {
        Mail::raw('This is a test email from EzShip Backend. If you received this, your email configuration is working correctly!', function($message) use ($testEmail) {
            $message->to($testEmail)
                    ->subject('EzShip Test Email - ' . date('Y-m-d H:i:s'));
        });
        
        $response['status'] = 'success';
        $response['message'] = "Test email sent successfully to {$testEmail}";
        $response['test_result'] = [
            'recipient' => $testEmail,
            'sent_at' => date('Y-m-d H:i:s'),
            'subject' => 'EzShip Test Email'
        ];
    } catch (\Exception $e) {
        $response['status'] = 'error';
        $response['message'] = 'Failed to send test email';
        $response['errors'][] = $e->getMessage();
        
        // Common error explanations
        if (strpos($e->getMessage(), 'Username and Password not accepted') !== false) {
            $response['fix'] = 'You need to use an App Password, not your Gmail password. See GMAIL_SETUP.md for instructions.';
        } elseif (strpos($e->getMessage(), 'Connection could not be established') !== false) {
            $response['fix'] = 'Cannot connect to Gmail SMTP. Try port 465 with SSL instead of 587 with TLS.';
        } elseif (strpos($e->getMessage(), 'Failed to authenticate') !== false) {
            $response['fix'] = 'Authentication failed. Check your email and app password in .env file.';
        }
    }
} else {
    $response['status'] = 'ready';
    $response['message'] = 'Email configuration loaded. To send a test email, use: ?send=1&to=your-email@example.com';
    $response['usage'] = [
        'test_send' => 'https://api.ezship.app/test-email.php?send=1&to=your-email@example.com',
        'check_only' => 'https://api.ezship.app/test-email.php'
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);