<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\API\QuickBooksController;

class TestQuickBooksIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test QuickBooks integration configuration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing QuickBooks Integration...');
        
        $controller = new QuickBooksController();
        
        // Test refresh token
        $this->info('Testing token refresh...');
        $result = $controller->refreshToken();
        
        if (isset($result['error'])) {
            $this->warn('QuickBooks is not configured: ' . $result['error']);
            $this->info('');
            $this->info('To configure QuickBooks:');
            $this->info('1. Register your app at https://developer.intuit.com');
            $this->info('2. Get your Client ID and Client Secret');
            $this->info('3. Create the Basic token by base64 encoding: clientId:clientSecret');
            $this->info('4. Add these values to your .env file:');
            $this->info('   QUICKBOOKS_BASIC_TOKEN=your_base64_encoded_token');
            $this->info('   QUICKBOOKS_REALM_ID=your_company_id');
            $this->info('5. Complete OAuth flow to get refresh token');
        } else {
            $this->info('QuickBooks token refresh successful!');
        }
        
        // Test customer creation (mock)
        $this->info('');
        $this->info('Testing customer creation with mock data...');
        $mockRequest = (object) [
            'id' => 'test-id',
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];
        
        $result = $controller->createCustomer($mockRequest);
        
        if (isset($result['status'])) {
            if ($result['status'] === 'skipped') {
                $this->warn('Customer creation skipped: ' . $result['message']);
            } elseif ($result['status'] === 'error') {
                $this->error('Customer creation failed: ' . $result['message']);
            } else {
                $this->info('Customer creation would work when QuickBooks is configured!');
            }
        }
        
        $this->info('');
        $this->info('QuickBooks integration test complete!');
        
        return Command::SUCCESS;
    }
}