<?php

// Fix Passport tables to support UUID user_ids
// This file should be deleted after use

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../vendor/autoload.php';
$app = require_once '../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$response = [
    'status' => 'running',
    'steps' => [],
    'errors' => []
];

try {
    // Step 1: Check current schema
    $response['steps'][] = 'Checking current schema...';
    $hasAccessTokensTable = Schema::hasTable('oauth_access_tokens');
    $hasAuthCodesTable = Schema::hasTable('oauth_auth_codes');
    $hasClientsTable = Schema::hasTable('oauth_clients');
    
    $response['tables'] = [
        'oauth_access_tokens' => $hasAccessTokensTable,
        'oauth_auth_codes' => $hasAuthCodesTable,
        'oauth_clients' => $hasClientsTable
    ];

    // Step 2: Modify oauth_access_tokens
    if ($hasAccessTokensTable) {
        $response['steps'][] = 'Modifying oauth_access_tokens table...';
        
        // Check column type
        $columnType = DB::select("
            SELECT data_type 
            FROM information_schema.columns 
            WHERE table_name = 'oauth_access_tokens' 
            AND column_name = 'user_id'
        ");
        
        if (!empty($columnType) && $columnType[0]->data_type !== 'uuid') {
            // Drop and recreate the column
            DB::statement('ALTER TABLE oauth_access_tokens DROP COLUMN IF EXISTS user_id CASCADE');
            DB::statement('ALTER TABLE oauth_access_tokens ADD COLUMN user_id UUID');
            DB::statement('CREATE INDEX IF NOT EXISTS oauth_access_tokens_user_id_index ON oauth_access_tokens(user_id)');
            $response['oauth_access_tokens'] = 'Modified to UUID';
        } else {
            $response['oauth_access_tokens'] = 'Already UUID or column missing';
        }
    }

    // Step 3: Modify oauth_auth_codes
    if ($hasAuthCodesTable) {
        $response['steps'][] = 'Modifying oauth_auth_codes table...';
        
        $columnType = DB::select("
            SELECT data_type 
            FROM information_schema.columns 
            WHERE table_name = 'oauth_auth_codes' 
            AND column_name = 'user_id'
        ");
        
        if (!empty($columnType) && $columnType[0]->data_type !== 'uuid') {
            DB::statement('ALTER TABLE oauth_auth_codes DROP COLUMN IF EXISTS user_id CASCADE');
            DB::statement('ALTER TABLE oauth_auth_codes ADD COLUMN user_id UUID');
            DB::statement('CREATE INDEX IF NOT EXISTS oauth_auth_codes_user_id_index ON oauth_auth_codes(user_id)');
            $response['oauth_auth_codes'] = 'Modified to UUID';
        } else {
            $response['oauth_auth_codes'] = 'Already UUID or column missing';
        }
    }

    // Step 4: Modify oauth_clients
    if ($hasClientsTable) {
        $response['steps'][] = 'Modifying oauth_clients table...';
        
        $columnType = DB::select("
            SELECT data_type 
            FROM information_schema.columns 
            WHERE table_name = 'oauth_clients' 
            AND column_name = 'user_id'
        ");
        
        if (!empty($columnType) && $columnType[0]->data_type !== 'uuid') {
            DB::statement('ALTER TABLE oauth_clients DROP COLUMN IF EXISTS user_id CASCADE');
            DB::statement('ALTER TABLE oauth_clients ADD COLUMN user_id UUID');
            DB::statement('CREATE INDEX IF NOT EXISTS oauth_clients_user_id_index ON oauth_clients(user_id)');
            $response['oauth_clients'] = 'Modified to UUID';
        } else {
            $response['oauth_clients'] = 'Already UUID or column missing';
        }
    }

    // Step 5: Clear caches
    $response['steps'][] = 'Clearing caches...';
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    
    $response['status'] = 'success';
    $response['message'] = 'Passport tables have been modified to support UUID user_ids';

} catch (\Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Failed to modify tables';
    $response['error'] = $e->getMessage();
    $response['trace'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT);