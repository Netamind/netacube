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
        // MIGRATION 2: retail_branch_products
Schema::create('retail_branch_products', function (Blueprint $table) {
            $table->id();
            // ── Parent References ─────────────────────────────────────────
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->onDelete('cascade');

            $table->unsignedBigInteger('base_product_id');
            $table->foreign('base_product_id')
                ->references('id')->on('retail_base_products')
                ->onDelete('cascade');

            // ── Barcode(s) ────────────────────────────────────────────────
            // Barcode lives here, NOT in base_products, because:
            // 1. The same product can arrive with a different supplier barcode at each branch.
            // 2. A branch may apply their own sticker barcode.
            // 3. A product can have multiple barcodes (EAN-13, UPC, QR) at the same branch.
            // We handle this with a separate retail_product_barcodes table below,
            // but we keep a "primary_barcode" here for quick POS scanning lookup.
            $table->string('primary_barcode')->nullable();

            // ── Batch / Expiry ────────────────────────────────────────────
            // Expiry is per batch at each branch, NOT a fixed product property.
            // A product sitting on shelf at Branch A expires on a different date
            // than the same product at Branch B (different delivery dates).
            // For products with multiple batches, use a retail_product_batches table later.
            // This column holds the CURRENT / ACTIVE batch expiry date.
            $table->date('expiry_date')->nullable();

            // Batch/lot number from the supplier for traceability
            $table->string('batch_number')->nullable();

            // ── Pricing (branch-specific) ─────────────────────────────────
            // The branch's selling price for this product (VAT-inclusive in Malawi retail).
            // Each branch sets its own price. Mandatory — a branch must have a price to sell.
            $table->decimal('selling_price', 15, 2);

            // The branch's cost price for this product (what they paid the supplier).
            // Used for margin calculations and profitability reports.
            $table->decimal('cost_price', 15, 2)->nullable();

            // Optional: if a branch wants to offer a member/wholesale price
            $table->decimal('wholesale_price', 15, 2)->nullable();

            // Currency is always MWK per MRA EIS (no multi-currency support in EIS).
            // We store it anyway for the non-EIS path.
            $table->string('currency', 3)->default('MWK');

            // ── MRA Tax Rate Override ─────────────────────────────────────
            // Normally inherited from retail_base_products.mra_tax_rate_id.
            // Override here ONLY if this branch is NOT VAT registered (use "EX")
            // or if MRA has given a specific classification at branch level.
            // NULL means "use the base product's tax rate" — the POS logic resolves this.
            $table->string('mra_tax_rate_id_override')->nullable();

            // ── Stock Management ──────────────────────────────────────────
            $table->decimal('stock_quantity', 12, 3)->default(0);
            // Alert when stock falls to or below this level
            $table->decimal('reorder_point', 12, 3)->default(0);
            // The quantity to order when restocking (suggested order quantity)
            $table->decimal('reorder_quantity', 12, 3)->nullable();
            // Maximum stock this branch should hold (for over-stocking alerts)
            $table->decimal('max_stock', 12, 3)->nullable();
            // Should the POS track and decrement stock on sale?
            $table->boolean('track_stock')->default(true);

            // ── Branch-Level Status ───────────────────────────────────────
            // A product can be active globally but disabled at a specific branch
            // e.g. Branch A doesn't stock this product
            $table->boolean('is_active')->default(true);

            // Can this product be sold even when stock_quantity = 0?
            // Useful for made-to-order or services linked to stock
            $table->boolean('allow_negative_stock')->default(false);

            // ── POS Display Hints ─────────────────────────────────────────
            // Show on the POS quick-select grid? (favourite/pinned items)
            $table->boolean('is_pinned_on_pos')->default(false);
            // POS display sort order within its category
            $table->integer('pos_sort_order')->default(0);

            // ── Timestamps & Unique Constraint ───────────────────────────
            $table->timestamps();

            // A product appears only once per branch
            $table->unique(['branch_id', 'base_product_id'], 'rbp_branch_product_unique');

            $table->index('primary_barcode');
            $table->index(['branch_id', 'is_active']);
            $table->index('expiry_date'); // useful for expiry alert queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retail_branch_products');
    }
};
