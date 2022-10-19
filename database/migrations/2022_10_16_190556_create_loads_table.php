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
        Schema::create('loads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->text('description')->nullable();
            $table->double('initial_price')->default(0.00);
            $table->double('confirmed_price')->nullable();
            $table->string('pickup_address');
            $table->date('pickup_date');
            $table->string('delivery_address');
            $table->date('delivery_date');
            $table->string('phone');
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
        Schema::dropIfExists('loads');
    }
};
