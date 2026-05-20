<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * retail_fullstocktaking — one row per (date, branch, base_product).
 *
 * SALES-NETTING APPROACH (avoids timestamp comparison):
 * ──────────────────────────────────────────────────────
 * Instead of comparing sold_at > counted_at (fragile due to clock skew
 * across POS devices, timezones, or apps writing backdated times),
 * we snapshot the MAX(id) of retail_system_sales for this branch+product
 * at the exact moment the product is first merged in (sales_id_at_count).
 *
 * At rectification:
 *   sales_since_count = SUM(quantity)
 *                       FROM retail_system_sales
 *                       WHERE branch = branch_id
 *                         AND productid = base_product_id
 *                         AND id > sales_id_at_count
 *
 *   expected_final = MAX(0, expected_at_count - sales_since_count)
 *
 * Because retail_system_sales.id is a monotonically increasing integer
 * (auto-increment), "id > snapshot" is guaranteed to capture every sale
 * that physically entered the database after the count — regardless of
 * what clock time was stored on the sale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_fullstocktaking', function (Blueprint $table) {
            $table->id();

            // ── Identity ─────────────────────────────────────────────────
            $table->date('date')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('base_product_id')->index();

            // ── Product snapshot (denormalised for history resilience) ────
            $table->string('product_name', 255);
            $table->string('unit', 60)->default('Each');
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('rate', 10, 4)->default(1.00);

            // ── Count snapshot ────────────────────────────────────────────
            // Stock quantity on the shelf the moment this product was first counted.
            $table->decimal('expected_at_count', 15, 4)->default(0);

            // MAX(retail_system_sales.id) for this branch+product at count time.
            // NULL means no sales existed yet when this product was counted —
            // interpreted as "all sales for this product are post-count".
            $table->unsignedBigInteger('sales_id_at_count')->nullable();

            // Accumulated physical count (additive across multiple merges / devices).
            $table->decimal('found', 15, 4)->default(0);

            // How many merge operations contributed to this row.
            $table->unsignedInteger('merge_count')->default(1);

            // JSON array of device IDs that contributed counts.
            $table->json('source_device_ids')->nullable();

            $table->string('last_synced_client_uuid', 120)->nullable();
            // ── Rectification result (written by submitRectification) ─────
            // expected_final = expected_at_count - sales_since_count
            $table->decimal('expected_final', 15, 4)->nullable();

            $table->enum('status', ['counted', 'rectified'])->default('counted')->index();
            $table->unsignedBigInteger('rectified_by_user_id')->nullable();
            $table->timestamp('rectified_at')->nullable();

            // ── Audit ─────────────────────────────────────────────────────
            $table->unsignedBigInteger('counted_by_user_id')->nullable();
            $table->timestamps();

            // ── Uniqueness: one merged row per product per date+branch ─────
            $table->unique(['date', 'branch_id', 'base_product_id'], 'fst_unique_date_branch_product');

            $table->index(['date', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_fullstocktaking');
    }
};