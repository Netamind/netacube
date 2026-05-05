<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_inventory_logs', function (Blueprint $table) {
            $table->id();

            // Core References
            $table->foreignId('product_id')->constrained('retail_base_products')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();

            // Stock Movement
            $table->decimal('stock_before', 15, 4);
            $table->decimal('stock_after', 15, 4);
            $table->decimal('stock_change', 15, 4); // positive = in, negative = out

            // Reason
            $table->text('action_reason');

            // User Details
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('user_device_details')->nullable();

            // Date & Time
            $table->date('log_date');
            $table->time('log_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_inventory_logs');
    }
};