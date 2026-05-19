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
        Schema::create('retail_physical_cash', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One physical-cash record per branch, per day.
            $table->unique(['branch_id', 'date'], 'retail_physical_cash_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('retail_physical_cash');
    }
};