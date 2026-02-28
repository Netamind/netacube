<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('sector')->unique();
            $table->text('description')->nullable();
        });
       $defaultSectors = [
        [
            'sector' => 'Retail',
            'description' => 'The system helps retail businesses manage inventory in real-time, track sales by product/category/location, handle customer loyalty programs, generate daily/weekly sales reports, manage suppliers, and control stock levels to prevent overstocking or stockouts.',
        ],
        [
            'sector' => 'Wholesale',
            'description' => 'Designed to support bulk operations — the system allows wholesalers to manage large-volume orders, set tiered pricing, track credit limits for customers, monitor delivery schedules, manage warehouse stock across multiple locations, and generate profitability analysis per product line or customer.',
        ],
        [
            'sector' => 'Finance',
            'description' => 'Enables financial service providers to manage client portfolios, track loans & investments, handle fee/commission structures, generate financial reports & statements, manage compliance documents, and monitor transaction history & risk indicators.',
        ],
        [
            'sector' => 'Consultancy',
            'description' => 'Helps consulting firms manage client engagements, track billable hours & project milestones, prepare proposals & contracts, allocate consultants to projects, generate performance reports, manage knowledge base & templates, and analyze profitability per client/project.',
        ],
        [
            'sector' => 'IT',
            'description' => 'Assists IT companies & MSPs in managing support tickets, service level agreements (SLAs), asset & license inventory, remote monitoring data, project-based development tasks, recurring maintenance contracts, and generating uptime/performance reports for clients.',
        ],
        [
            'sector' => 'Healthcare',
            'description' => 'Supports clinics & small hospitals by managing patient records (where permitted), appointment scheduling, prescription tracking, inventory of medicines & supplies, billing & insurance claims, staff duty rosters, and generating compliance & revenue reports.',
        ],
        [
            'sector' => 'Hospitality',
            'description' => 'Helps hotels, lodges, restaurants & event venues manage room bookings, table reservations, POS sales, banquet/event scheduling, staff shifts, inventory of food & beverages, guest profiles, housekeeping status, and detailed revenue & occupancy analytics.',
        ],
        [
            'sector' => 'Properties',
            'description' => 'Ideal for real estate agencies, property managers & landlords — track properties, units & tenants, manage lease agreements, record rent payments & arrears, handle maintenance requests & costs, generate income & expense reports per property, and monitor occupancy & vacancy trends.',
        ]
    ];
        DB::table('sectors')->insert($defaultSectors);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sectors');
    }
};