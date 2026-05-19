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

      DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::create('retail_interval_sales', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('interval_id')->constrained('retail_intervals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('sales', 15, 2);
            $table->timestamps();

            // One entry per branch, per day, per interval slot.
            $table->unique(['branch_id', 'date', 'interval_id'], 'retail_interval_sales_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('retail_interval_sales');
    }
};