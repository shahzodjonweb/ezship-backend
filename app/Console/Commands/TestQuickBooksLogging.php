<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\API\QuickBooksController;

class TestQuickBooksLogging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:test-logging';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test QuickBooks logging functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing QuickBooks logging...');
        
        // Test different log levels
        Log::channel('quickbooks')->debug('Debug test message', ['test' => 'debug']);
        Log::channel('quickbooks')->info('Info test message', ['test' => 'info']);
        Log::channel('quickbooks')->warning('Warning test message', ['test' => 'warning']);
        Log::channel('quickbooks')->error('Error test message', ['test' => 'error']);
        
        $this->info('Testing QuickBooks Controller initialization...');
        
        // Initialize QuickBooks controller to trigger constructor logging
        try {
            $controller = new QuickBooksController();
            $this->info('QuickBooks Controller initialized successfully');
            
            // Test token refresh (will fail if not configured, but will log)
            $this->info('Testing token refresh...');
            $result = $controller->refreshToken();
            
            if (isset($result['error'])) {
                $this->warn('QuickBooks not configured: ' . ($result['message'] ?? $result['error']));
            } else {
                $this->info('Token refresh successful!');
            }
            
        } catch (\Exception $e) {
            $this->error('Error during testing: ' . $e->getMessage());
            Log::channel('quickbooks')->error('Test command error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        $this->info('');
        $this->info('QuickBooks logging test complete!');
        $this->info('Check the log file at: storage/logs/quickbooks-' . date('Y-m-d') . '.log');
        
        return Command::SUCCESS;
    }
}
