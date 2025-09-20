<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Credential;

class SetupInitialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'initial:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup initial data';

    /**
     * Execute the console command.
     *
     * @return int
     * 
     * website for getting refresh token
     * https://developer.intuit.com/app/developer/playground?code=XAB11757198482cJIcO0QhUyArzXzxUEgadpAR9OLKbl7HDHgk&state=PlaygroundAuth&realmId=4620816365265861860
     */
    public function handle()
    {
        // Check if QuickBooks credentials already exist
        $existing = Credential::where('name', 'quickbooks')->first();
        
        if ($existing) {
            $this->info('QuickBooks credentials already exist, skipping setup.');
            return Command::SUCCESS;
        }
        
        // Create initial quickbook credentials
        $this->info('Creating initial QuickBooks credentials...');
        
        $credential = new Credential();
        $credential->name = 'quickbooks';
        $credential->refresh_token = env('QUICKBOOKS_REFRESH_TOKEN', 'RT1-244-H0-176592584666rivmm8b1180jihjta3');
        $credential->access_token = '';
        $credential->save();
        
        $this->info('✓ QuickBooks credentials created successfully.');

        return Command::SUCCESS;
    }
}
