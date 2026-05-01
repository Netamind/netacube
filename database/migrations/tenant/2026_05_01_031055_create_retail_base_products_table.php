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
        Schema::create('retail_base_products', function (Blueprint $table) {
            $table->id();

            // ── Core Identity ─────────────────────────────────────────────
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            // The primary/default supplier for this product at the base level.
            // Branches may source from different suppliers — that is tracked on
            // retail_branch_products or a future purchase_orders table.
            $table->string('supplier')->nullable();
            $table->string('country_of_origin', 2)->nullable();      // ISO 3166-1 alpha-2: "MW", "ZA"

            // ── Internal Reference ────────────────────────────────────────
            // YOUR system's permanent SKU/code for this product.
            // Used as the match key during Excel imports and barcode linking.
            // Different from mra_product_code — that is MRA's classification code.
            $table->string('internal_code')->unique()->nullable();

            // ── Physical / Measurement ────────────────────────────────────
            // unit_of_measure: the sellable unit shown on MRA EIS invoices
            //   (maps to the quantity unit in invoiceLineItems[]).
            //   Defaults to "Each" — the most common retail unit.
            $table->string('unit_of_measure')->default('Each');
            $table->decimal('weight_kg',     10, 4)->nullable();     // weight of ONE sellable unit, in kg
            $table->decimal('volume_litres', 10, 4)->nullable();     // volume of ONE sellable unit, in litres

            // ── Product vs Service ────────────────────────────────────────
            // Maps directly to invoiceLineItems[].isProduct in MRA EIS.
            // true  = physical product  → stock is tracked at branch level
            // false = service           → no stock, quantity tracking skipped
            $table->boolean('is_product')->default(true);

            // ── Default Pricing ───────────────────────────────────────────
            // System-wide standard prices used by ALL branches unless a branch
            // product record provides its own override.
            //
            // RESOLUTION AT POINT OF SALE:
            //   selling_price = branch_product.selling_price
            //                   ?? base_product.default_selling_price
            //   cost_price    = branch_product.cost_price
            //                   ?? base_product.default_cost_price
            //
            // Both are nullable so a product can exist in the catalogue before
            // pricing is finalised (catalogue entry stage).
            // All values are in MWK — MRA EIS does not support multi-currency.
            $table->decimal('default_selling_price', 15, 2)->nullable();
            $table->decimal('default_cost_price',    15, 2)->nullable();

            // ── MRA EIS Classification ────────────────────────────────────
            // mra_product_code:
            //   UN/SPSC-based category code registered and approved on the
            //   MRA EIS Portal before invoicing is permitted.
            //   Maps to invoiceLineItems[].productCode in the EIS API.
            //   e.g. "50201700" = Packaged cereals, "10121500" = Live cattle
            $table->string('mra_product_code')->nullable();

            // mra_tax_rate_id:
            //   Tax rate identifier from MRA's globalConfiguration.taxRates,
            //   returned during terminal activation (get-latest-configuration).
            //   Maps to invoiceLineItems[].taxRateId in the EIS API.
            //   Current confirmed EIS rate IDs:
            //     "A"  = VAT-A  (Standard VAT 16.5%)
            //     "E"  = Exempt (zero-rated / VAT exempt goods)
            //     "TL" = Tourism Levy (1%)
            //   Your terminal's activatedTaxRateIds determine which are available.
            $table->string('mra_tax_rate_id', 10)->nullable();

            // is_vat_exempt_by_nature:
            //   True when this product is VAT exempt by its very nature,
            //   regardless of which branch sells it.
            //   e.g. unprocessed foodstuffs, medical supplies, agricultural inputs.
            //   When true, mra_tax_rate_id should always be "E".
            $table->boolean('is_vat_exempt_by_nature')->default(false);

            // ── Internal Categorisation ───────────────────────────────────
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();               // kept separate for flexible filtering

            // ── Image ─────────────────────────────────────────────────────
            $table->string('image_path')->nullable();

            // ── Global Status ─────────────────────────────────────────────
            // Inactive = discontinued globally; hidden from all branch listings.
            // Does NOT automatically deactivate existing branch product records —
            // branch-level status is managed on retail_branch_products.is_active.
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('internal_code');
            $table->index('mra_product_code');
            $table->index('mra_tax_rate_id');
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retail_base_products');
    }
};