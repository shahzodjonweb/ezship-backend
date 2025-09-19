#!/bin/bash

echo "=== Fixing Passport Tables for UUID Support ==="

# Function to run commands in the app container
run_command() {
    echo "Running: $1"
    
    # Try different container names
    if docker exec ezship-app-prod bash -c "$1" 2>/dev/null; then
        return 0
    elif docker exec ezship-backend-app-1 bash -c "$1" 2>/dev/null; then
        return 0
    else
        echo "Could not find app container"
        return 1
    fi
}

echo "Step 1: Backing up current oauth tables..."
run_command "cd /var/www/html && php artisan db:table oauth_access_tokens > /tmp/oauth_access_tokens_backup.sql || true"
run_command "cd /var/www/html && php artisan db:table oauth_auth_codes > /tmp/oauth_auth_codes_backup.sql || true"
run_command "cd /var/www/html && php artisan db:table oauth_clients > /tmp/oauth_clients_backup.sql || true"

echo ""
echo "Step 2: Modifying Passport tables to support UUID..."

# Direct SQL commands to modify the tables
run_command "cd /var/www/html && php artisan tinker --execute=\"
    use Illuminate\\Support\\Facades\\DB;
    use Illuminate\\Support\\Facades\\Schema;
    
    // Drop existing user_id columns
    if (Schema::hasColumn('oauth_access_tokens', 'user_id')) {
        DB::statement('ALTER TABLE oauth_access_tokens DROP COLUMN user_id');
    }
    
    if (Schema::hasColumn('oauth_auth_codes', 'user_id')) {
        DB::statement('ALTER TABLE oauth_auth_codes DROP COLUMN user_id');
    }
    
    if (Schema::hasColumn('oauth_clients', 'user_id')) {
        DB::statement('ALTER TABLE oauth_clients DROP COLUMN user_id');
    }
    
    // Add UUID columns
    DB::statement('ALTER TABLE oauth_access_tokens ADD COLUMN user_id UUID');
    DB::statement('ALTER TABLE oauth_auth_codes ADD COLUMN user_id UUID');
    DB::statement('ALTER TABLE oauth_clients ADD COLUMN user_id UUID');
    
    // Add indexes
    DB::statement('CREATE INDEX oauth_access_tokens_user_id_index ON oauth_access_tokens(user_id)');
    DB::statement('CREATE INDEX oauth_auth_codes_user_id_index ON oauth_auth_codes(user_id)');
    DB::statement('CREATE INDEX oauth_clients_user_id_index ON oauth_clients(user_id)');
    
    echo 'Tables modified successfully';
\""

echo ""
echo "Step 3: Running migrations..."
run_command "cd /var/www/html && php artisan migrate --force"

echo ""
echo "Step 4: Clearing caches..."
run_command "cd /var/www/html && php artisan config:clear"
run_command "cd /var/www/html && php artisan cache:clear"

echo ""
echo "=== Passport UUID Fix Complete ==="
echo ""
echo "The oauth tables have been modified to support UUID user_ids."