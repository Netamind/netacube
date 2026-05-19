<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per cart line item — same shape as the old retailsales table,
     * with product/unit/price kept as denormalized snapshot columns (so a
     * historical sale always reads back exactly as it was sold, even if the
     * product is later renamed, re-priced, or deleted), plus a proper FK
     * into retail_branch_products for referential integrity and joins.
     *
     * Each row also captures the stock movement (qty_before/qty_sold/qty_after)
     * and basic device/audit info for the user who recorded the sale.
     */
    public function up(): void
    
    {
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::create('retail_system_sales', function (Blueprint $table) {
            $table->id();

            $table->string('transid', 165);   // groups line items into one transaction/receipt
            $table->string('date', 165);
            $table->string('time', 165);

            // Reference into retail_branch_products for joins/integrity...
            $table->foreignId('branch_product_id')->constrained('retail_branch_products')->cascadeOnDelete();

            // ...but also keep the old denormalized snapshot columns, so a
            // sale's product name/unit/price never changes even if the
            // underlying product record is edited or removed later.
            $table->string('product', 165);
            $table->string('unit', 165);
            $table->string('price', 165);

            $table->string('user', 165);
            $table->string('branch', 165);

            $table->decimal('quantity', 65, 2);                 // original old-schema field — kept for compatibility
            $table->decimal('rquantity', 65, 2)->default(0.00); // returned/refunded quantity

            // Stock movement snapshot at the moment of sale.
            $table->decimal('qty_before', 65, 2)->default(0.00);
            $table->decimal('qty_sold', 65, 2)->default(0.00);
            $table->decimal('qty_after', 65, 2)->default(0.00);

            $table->string('payment_method', 30)->default('cash'); // cash, airtel, mpamba, bank
            $table->decimal('amount_paid', 65, 2)->nullable();      // amount tendered, for change calc on cash sales

            $table->string('slot', 165)->default('0'); // informational snapshot of the interval, not a FK

            // Device / audit info for whoever recorded the sale.
            $table->string('device_name', 165)->nullable();
            $table->string('ip_address', 45)->nullable();   // 45 = max length of an IPv6 address
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->unique(['branch', 'branch_product_id', 'transid', 'date'], 'retail_system_sales_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('retail_system_sales');
    }
};