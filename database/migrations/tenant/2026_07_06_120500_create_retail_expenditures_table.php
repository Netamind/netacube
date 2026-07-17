<?php
// 2026_07_06_120500_create_retail_expenditures_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_expenditures', function (Blueprint $table) {
            $table->id();

            // ── Type ─────────────────────────────────────────────────────
            $table->unsignedBigInteger('expenditure_type_id');

            // ── Scope ────────────────────────────────────────────────────
            // 'all'      → applies to the entire Retail sector
            // 'category' → applies to one specific category under Retail
            // 'branch'   → applies to one specific branch under Retail
            $table->enum('scope_type', ['all', 'category', 'branch'])->default('all');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();

            // ── Amount / date ────────────────────────────────────────────
            $table->decimal('amount', 15, 2);
            $table->date('expenditure_date');
            $table->string('reference_no', 100)->nullable();
            $table->text('description')->nullable();

            // ── Audit ────────────────────────────────────────────────────
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // ── Foreign keys ─────────────────────────────────────────────
            // restrict: an expenditure type in use should be deactivated,
            // not deleted out from under historical spend records.
            $table->foreign('expenditure_type_id')
                  ->references('id')->on('retail_expenditure_types')
                  ->onDelete('restrict');

            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->onDelete('cascade');

            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');

            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            $table->index(['scope_type', 'category_id', 'branch_id'], 'exp_scope_idx');
            $table->index('expenditure_date', 'exp_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_expenditures');
    }
};