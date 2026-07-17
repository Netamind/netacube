<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A stable share link per branch+category+supplier. There's no batch
     * to hang a token off anymore, and each supplier gets its OWN link
     * (plus one more for "All Suppliers") — e.g.
     *   netacube.net/apollo/order/express-pharmacy/all-suppliers/{token}
     *   netacube.net/apollo/order/express-pharmacy/medico-ltd/{token}
     * `supplier_id` is null for the "all-suppliers" row; every other row
     * is one specific supplier. The branch/supplier names in the URL path
     * are cosmetic slugs for readability only — the actual lookup is by
     * `share_token` alone, so the link keeps working (and keeps refreshing
     * with live data) even if a branch or supplier is later renamed.
     */
    public function up(): void
    {
        Schema::create('retail_order_links', function (Blueprint $table) {
            $table->id();

            $table->string('share_token', 64)->unique();
            $table->unsignedBigInteger('branch_id');
            $table->enum('category', ['Regular', 'Emergency', 'Rare']);

            // Null = the "All Suppliers" link for this branch+category.
            // Not part of a DB unique constraint below (nullable columns
            // don't reliably enforce "only one null row" across database
            // engines) — one-row-per-combo is checked in app code before
            // insert instead, same pattern already used for pending order
            // line uniqueness elsewhere in this module.
            $table->unsignedBigInteger('supplier_id')->nullable();

            $table->boolean('share_enabled')->default(true);
            $table->timestamp('share_last_viewed_at')->nullable();
            $table->unsignedInteger('share_view_count')->default(0);

            $table->timestamps();

            // Fast lookup when re-creating/reusing a link for a given
            // branch+category+supplier — NOT a DB-enforced uniqueness
            // guarantee, see note on supplier_id above.
            $table->index(['branch_id', 'category', 'supplier_id']);

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_order_links');
    }
};