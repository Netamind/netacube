<?php

//branch products new system 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

            // ── Barcode ───────────────────────────────────────────────────
            $table->string('primary_barcode')->nullable();

            // ── Batch / Expiry ────────────────────────────────────────────
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();

            // ── Pricing ───────────────────────────────────────────────────
            $table->decimal('selling_price',   15, 2)->nullable();
            $table->decimal('cost_price',      15, 2)->nullable();

            // ── Stock ─────────────────────────────────────────────────────
            $table->decimal('stock_quantity',   12, 2)->default(0);
            $table->decimal('reorder_point',    12, 2)->default(0);
            $table->decimal('reorder_quantity', 12, 2)->nullable();
            $table->decimal('max_stock',        12, 2)->nullable();
            $table->boolean('track_stock')->default(true);

            // ── Status ────────────────────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_negative_stock')->default(false);

            $table->timestamps();

            $table->unique(['branch_id', 'base_product_id'], 'rbp_branch_product_unique');
            $table->index('primary_barcode');
            $table->index(['branch_id', 'is_active']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_branch_products');
    }
};