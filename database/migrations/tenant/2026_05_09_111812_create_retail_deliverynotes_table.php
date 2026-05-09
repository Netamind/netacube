<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_deliverynotes', function (Blueprint $table) {
            $table->id();

            // ── Core references ───────────────────────────────────────────
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');

            $table->unsignedBigInteger('base_product_id');
            $table->foreign('base_product_id')->references('id')->on('retail_base_products')->onDelete('restrict');

            // ── Product snapshot ──────────────────────────────────────────
            // Frozen at entry time — stays accurate if product is renamed/repriced later.
            $table->string('product_name');
            $table->string('product_code')->nullable();
            $table->string('product_unit')->default('Each');
            $table->decimal('cost_price',    15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);

            // ── Delivery details ──────────────────────────────────────────
            $table->date('delivery_date');
            $table->decimal('quantity', 12, 3);              // quantity on the note

            // ── Submission ────────────────────────────────────────────────
            // submitted = true  → quantity has been added to branch stock
            // submitted = false → pending, no stock impact yet
            $table->boolean('submitted')->default(false);
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamp('submitted_at')->nullable();

            // ── Added by ──────────────────────────────────────────────────
            $table->unsignedBigInteger('added_by');
            $table->foreign('added_by')->references('id')->on('users')->onDelete('restrict');

            // ── Discrepancy ───────────────────────────────────────────────
            // Signed: +5 = received 5 more, -4 = received 4 less than quantity above.
            // Null means no discrepancy recorded yet.
            $table->decimal('error_quantity', 12, 3)->nullable();
            $table->unsignedBigInteger('error_recorded_by')->nullable();
            $table->foreign('error_recorded_by')->references('id')->on('users')->onDelete('restrict');
            $table->text('error_notes')->nullable();
            $table->enum('error_status', ['Pending', 'Sorted', 'Rejected'])->nullable();

            // ── Notes ─────────────────────────────────────────────────────
            $table->text('notes')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────
            $table->index(['branch_id', 'delivery_date'], 'rdn_branch_date');
            $table->index('base_product_id',              'rdn_product');
            $table->index('submitted',                    'rdn_submitted');
            $table->index('error_status',                 'rdn_error_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_deliverynotes');
    }
};