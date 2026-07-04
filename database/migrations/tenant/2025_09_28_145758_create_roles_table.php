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

            [
                'role'        => 'Admin',
                'description' => 'Full administrative access within permitted sectors. Can manage categories, employees, business operations, reports, and sector-level settings.',
            ],
            [
                'role'        => 'Accounts',
                'description' => 'Responsible for financial operations: managing invoices, payments, receipts, expense tracking, bank reconciliations, financial reports, and basic accounting within the assigned company.',
            ],
            [
                'role'        => 'Sales',
                'description' => 'Focused on revenue generation: managing leads, customers, sales pipeline, quotations, orders, follow-ups, commissions, and sales performance reporting.',
            ],
            [
                'role'        => 'Operations',
                'description' => 'Handles day-to-day business execution: logistics, inventory management, service delivery, process coordination, staff scheduling, and operational performance monitoring.',
            ],
            [
                'role'        => 'Investor',
                'description' => 'Read-only access (or limited write permissions where allowed) to financial summaries, performance reports, investment tracking, profit & loss statements, and key business metrics.',
            ],
            [
                'role'        => 'Manager',
                'description' => 'Departmental or branch-level oversight: access to team performance, reports, approvals, and limited administrative functions within their scope.',
            ],
            [
                'role'        => 'Staff',
                'description' => 'Standard employee access: can view personal profile, finances, leave balance, assigned tasks, and submit requests.',
            ],
            [
                'role'        => 'NSU',
                'description' => 'Non-system user: individuals who cannot log in to the system',
            ],
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
