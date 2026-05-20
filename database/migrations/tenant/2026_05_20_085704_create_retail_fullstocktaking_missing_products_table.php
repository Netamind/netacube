<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * retail_fullstocktaking_missing_products
 * ─────────────────────────────────────────────────────────────────────────
 * Products that were never counted during a stocktake for a given
 * date + branch.  Seeded lazily the first time the Missing Products tab
 * is opened; rows persist across reloads so offline edits survive.
 *
 * client_uuid / last_synced_at support the offline queue pattern:
 *   - each sync op carries a client_uuid
 *   - we store the last applied uuid on the row so retried batches
 *     are idempotent (duplicate uuid → skip, not double-apply)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_fullstocktaking_missing_products', function (Blueprint $table) {
            $table->id();

            $table->date('date')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('base_product_id');

            // Product snapshot
            $table->string('product_name', 255);
            $table->string('unit', 60)->default('Each');
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('rate', 10, 4)->default(1.00);

            // Current quantity at seeding time (from retail_branch_products).
            // Editable offline via the Missing Products tab.
            $table->decimal('quantity', 15, 4)->default(0);

            // Optional inventory metadata snapshotted at seeding time
            $table->string('batch_number', 120)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('product_status', 40)->nullable();

            // Offline-sync idempotency
            $table->string('client_uuid', 120)->nullable();
            $table->unsignedBigInteger('last_edited_by_user_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['date', 'branch_id', 'base_product_id'],
                'fst_missing_unique_date_branch_product'
            );
            $table->index(['date', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_fullstocktaking_missing_products');
    }
};