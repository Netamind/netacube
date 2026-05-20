<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * retail_fullstocktaking_summary
 * ─────────────────────────────────────────────────────────────────────────
 * Permanent audit record written once per (date, branch) at rectification
 * time.  Its existence is also the "is rectified?" gate used by all four
 * tab views and the merge endpoint — if a row exists here for this
 * date+branch, that stocktake is locked.
 *
 * All monetary values here are the FINAL, sales-netted figures
 * (expected_final, not expected_at_count).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_fullstocktaking_summary', function (Blueprint $table) {
            $table->id();

            $table->date('date')->index();
            $table->unsignedBigInteger('branch_id')->index();

            // ── Counted products ──────────────────────────────────────────
            $table->unsignedInteger('products_counted')->default(0);
            $table->unsignedInteger('products_no_anomaly')->default(0);
            $table->unsignedInteger('products_overage')->default(0);
            $table->unsignedInteger('products_shortage')->default(0);

            // ── Financial totals (sales-netted) ───────────────────────────
            $table->decimal('expected_value', 18, 4)->default(0);   // SUM(expected_final * price)
            $table->decimal('found_value', 18, 4)->default(0);       // SUM(found * price)
            $table->decimal('overage_value', 18, 4)->default(0);
            $table->decimal('shortage_value', 18, 4)->default(0);
            $table->decimal('difference_value', 18, 4)->default(0); // found_value - expected_value

            // ── Missing products ──────────────────────────────────────────
            $table->unsignedInteger('missing_count')->default(0);
            $table->decimal('missing_value', 18, 4)->default(0);

            // ── Full difference: (FV - EV) - MV ──────────────────────────
            $table->decimal('full_difference_value', 18, 4)->default(0);

            // ── Audit ─────────────────────────────────────────────────────
            $table->unsignedBigInteger('rectified_by_user_id')->nullable();
            $table->string('device_details', 512)->nullable();

            $table->timestamps();

            $table->unique(['date', 'branch_id'], 'fst_summary_unique_date_branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_fullstocktaking_summary');
    }
};