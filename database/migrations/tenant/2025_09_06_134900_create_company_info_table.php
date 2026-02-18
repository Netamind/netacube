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
        Schema::create('company_info', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('business_license_number')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('business_description')->nullable();
            $table->string('business_mission')->nullable();
            $table->string('business_vision')->nullable();
            $table->string('primary_number')->nullable();
            $table->string('secondary_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('physical_address')->nullable();
            $table->string('postal_address')->nullable();
        });

        DB::table('company_info')->insert(['id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_info');
    }
};
