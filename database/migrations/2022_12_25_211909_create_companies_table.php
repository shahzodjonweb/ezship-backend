<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id');
            $table->string('name');
            $table->string('address');
            $table->string('business_phone');
            $table->string('business_email');
            $table->string('representative_name');
            $table->string('representative_position');
            $table->string('sales_person_name');
            $table->string('sales_phone');
            $table->string('sales_email');
            $table->string('billing_address');
            $table->string('payment_type')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('status')->default('inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
