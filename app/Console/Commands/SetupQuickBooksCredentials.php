<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Credential;
use Illuminate\Support\Facades\Log;

class SetupQuickBooksCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:setup-credentials 
                            {--refresh-token= : The QuickBooks refresh token}
                            {--access-token= : The QuickBooks access token (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup initial QuickBooks OAuth credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up QuickBooks credentials...');
        
        // Get tokens from options or prompt
        $refreshToken = $this->option('refresh-token');
        $accessToken = $this->option('access-token');
        
        if (!$refreshToken) {
            $this->warn('To get your refresh token, you need to:');
            $this->info('1. Go to: https://app.sandbox.qbo.intuit.com/app/homepage');
            $this->info('2. Complete OAuth authorization flow');
            $this->info('3. Get the refresh token from the OAuth response');
            $this->info('');
            
            $refreshToken = $this->ask('Enter your QuickBooks refresh token');
        }
        
        if (!$refreshToken) {
            $this->error('Refresh token is required!');
            return Command::FAILURE;
        }
        
        if (!$accessToken) {
            $accessToken = $this->ask('Enter your QuickBooks access token (optional, will be refreshed automatically)');
        }
        
        try {
            // Create or update QuickBooks credentials
            $credential = Credential::updateOrCreate(
                ['name' => 'quickbooks'],
                [
                    'access_token' => $accessToken ?: '',
                    'refresh_token' => $refreshToken,
                    'expires_at' => now()->addHour(), // Access tokens typically expire in 1 hour
                ]
            );
            
            $this->info('QuickBooks credentials saved successfully!');
            
            // Log the setup
            Log::channel('quickbooks')->info('QuickBooks credentials manually configured', [
                'has_access_token' => !empty($accessToken),
                'has_refresh_token' => true,
                'credential_id' => $credential->id
            ]);
            
            // Test the credentials
            $this->info('');
            $this->info('Testing credentials...');
            
            $controller = new \App\Http\Controllers\API\QuickBooksController();
            $result = $controller->refreshToken();
            
            if (isset($result['error'])) {
                $this->error('Failed to refresh token: ' . ($result['message'] ?? $result['error']));
                $this->warn('Please check your credentials and try again.');
            } else {
                $this->info('✅ Successfully refreshed token!');
                $this->info('QuickBooks integration is now ready to use.');
            }
            
        } catch (\Exception $e) {
            $this->error('Error saving credentials: ' . $e->getMessage());
            Log::channel('quickbooks')->error('Failed to setup credentials', [
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
