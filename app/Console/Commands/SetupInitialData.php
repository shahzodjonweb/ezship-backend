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
     */
    public function handle()
    {
        // Create initial quickbook credentials
        $credential = new Credential();
        $credential->name = 'quickbooks';
        $credential->refresh_token = 'AB11681843096MpEYr9i9wkcySMtEYigIhMurQmf7wJRIjXiO3';
        $credential->access_token = '';
        $credential->save();

        return Command::SUCCESS;
    }
}
