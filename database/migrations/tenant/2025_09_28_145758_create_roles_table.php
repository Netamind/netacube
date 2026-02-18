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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role')->unique();
            $table->text('description')->nullable();
        });

        DB::table('roles')->insert([
            ['role' => 'Sales', 'description' => 'Team members focused on generating revenue, managing customer relationships, and driving business growth'],
            ['role' => 'Admin', 'description' => 'System Administrator with full access and control'],
            ['role' => 'Accounts', 'description' => 'Users responsible for managing financial transactions, invoices and payments'],
            ['role' => 'Operations', 'description' => 'Personnel overseeing the day-to-day activities, logistics, and processes that keep the organization running smoothly'],
            ['role' => 'Investor', 'description' => 'Authorized individuals with access to financial information and investment details'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
