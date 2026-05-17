<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_price_changes', function (Blueprint $table) {
            $table->id();

            // ── Soft references ───────────────────────────────────────────
            $table->unsignedBigInteger('base_product_id')->nullable();
            $table->foreign('base_product_id')
                ->references('id')->on('retail_base_products')
                ->onDelete('set null');

            // null  = base catalogue price change
            // set   = branch-specific override change
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->onDelete('set null');

            $table->unsignedBigInteger('changed_by')->nullable();
            $table->foreign('changed_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            // ── Snapshot — record stays meaningful if product is deleted ──
            $table->string('product_name');
            $table->string('product_code')->nullable();
            $table->string('product_unit')->default('Each');
            $table->string('branch_name')->nullable();

            // ── Price ─────────────────────────────────────────────────────
            $table->decimal('old_price', 15, 2);
            $table->decimal('new_price', 15, 2);

            // ── Meta ──────────────────────────────────────────────────────
            $table->string('reason')->nullable();

            // Date the change was recorded — used for date-based filtering
            // Changes are effective immediately; this is not a schedule date
            $table->date('change_date');

            $table->timestamps();

            $table->index(['base_product_id', 'branch_id']);
            $table->index('change_date');
            $table->index('changed_by');
            $table->index('product_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_price_changes');
    }
};