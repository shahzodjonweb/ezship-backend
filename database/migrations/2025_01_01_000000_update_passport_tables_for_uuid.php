<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePassportTablesForUuid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update oauth_auth_codes table
        if (Schema::hasTable('oauth_auth_codes')) {
            Schema::table('oauth_auth_codes', function (Blueprint $table) {
                // Drop the existing user_id column if it exists
                if (Schema::hasColumn('oauth_auth_codes', 'user_id')) {
                    $table->dropColumn('user_id');
                }
            });
            
            Schema::table('oauth_auth_codes', function (Blueprint $table) {
                // Add user_id as UUID string
                $table->uuid('user_id')->after('id');
                $table->index('user_id');
            });
        }

        // Update oauth_access_tokens table
        if (Schema::hasTable('oauth_access_tokens')) {
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                // Drop the existing user_id column if it exists
                if (Schema::hasColumn('oauth_access_tokens', 'user_id')) {
                    $table->dropColumn('user_id');
                }
            });
            
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                // Add user_id as UUID string
                $table->uuid('user_id')->nullable()->after('id');
                $table->index('user_id');
            });
        }

        // Update oauth_clients table
        if (Schema::hasTable('oauth_clients')) {
            Schema::table('oauth_clients', function (Blueprint $table) {
                // Drop the existing user_id column if it exists
                if (Schema::hasColumn('oauth_clients', 'user_id')) {
                    $table->dropColumn('user_id');
                }
            });
            
            Schema::table('oauth_clients', function (Blueprint $table) {
                // Add user_id as UUID string (nullable for client credentials grant)
                $table->uuid('user_id')->nullable()->after('id');
                $table->index('user_id');
            });
        }

        // Update oauth_personal_access_clients table
        if (Schema::hasTable('oauth_personal_access_clients')) {
            // This table doesn't have user_id, only client_id
            // No changes needed
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert oauth_auth_codes table
        if (Schema::hasTable('oauth_auth_codes')) {
            Schema::table('oauth_auth_codes', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
            
            Schema::table('oauth_auth_codes', function (Blueprint $table) {
                $table->bigInteger('user_id');
            });
        }

        // Revert oauth_access_tokens table
        if (Schema::hasTable('oauth_access_tokens')) {
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
            
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable();
            });
        }

        // Revert oauth_clients table
        if (Schema::hasTable('oauth_clients')) {
            Schema::table('oauth_clients', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
            
            Schema::table('oauth_clients', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable();
            });
        }
    }
}