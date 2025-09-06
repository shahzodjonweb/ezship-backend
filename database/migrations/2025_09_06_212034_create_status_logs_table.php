<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('load_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->uuid('changed_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('load_id');
            $table->index('changed_by');
            $table->index('created_at');
            
            // Add foreign key constraints
            $table->foreign('load_id')->references('id')->on('loads')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_logs');
    }
};
