<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->index();

            // ── Location / organisational ────────────────────────────────
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();

            // ── Terminal / device ────────────────────────────────────────
            $table->unsignedBigInteger('terminal_id')->nullable();
            $table->string('endpoint', 60)->nullable();
            $table->string('outcome', 30)->nullable();

            // ── People ───────────────────────────────────────────────────
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('cashier_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            // ── Products / inventory ─────────────────────────────────────
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('action_product_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();

            // ── Sales / transactions ─────────────────────────────────────
            $table->string('payment_method', 40)->nullable();
            $table->string('transaction_type', 40)->nullable();
            $table->string('sale_type', 40)->nullable();
            $table->string('invoice_status', 40)->nullable();
            $table->string('payment_status', 40)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();

            // ── Purchasing / stock ───────────────────────────────────────
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('delivery_note_id')->nullable();
            $table->unsignedBigInteger('stock_take_id')->nullable();
            $table->unsignedBigInteger('adjustment_id')->nullable();
            $table->unsignedBigInteger('transfer_id')->nullable();

            // ── HR / Payroll ─────────────────────────────────────────────
            $table->string('sector', 100)->nullable();
            $table->unsignedBigInteger('wagebill_period_id')->nullable();

            // ── Payslips ─────────────────────────────────────────────────
            $table->unsignedBigInteger('payslip_period_id')->nullable();   // payroll period filter on payslip page
            $table->unsignedBigInteger('payslip_category_id')->nullable(); // category filter on payslip page
            $table->unsignedBigInteger('payslip_employee_id')->nullable(); // employee filter on payslip page

            // ── Dates ────────────────────────────────────────────────────
            $table->string('period', 30)->nullable();
            $table->date('date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('stock_take_custom_date')->nullable();
            $table->date('dnote_custom_date')->nullable();
            $table->date('po_custom_date')->nullable();
            $table->date('adjustment_custom_date')->nullable();
            $table->date('transfer_custom_date')->nullable();
            $table->date('report_date')->nullable();
            $table->integer('year')->nullable();
            $table->integer('month')->nullable();
            $table->integer('week')->nullable();
            $table->integer('quarter')->nullable();

            // ── Reports / display ────────────────────────────────────────
            $table->string('report_type', 60)->nullable();
            $table->string('report_format', 20)->nullable();
            $table->string('group_by', 40)->nullable();
            $table->string('sort_by', 60)->nullable();
            $table->string('sort_direction', 4)->nullable();
            $table->integer('per_page')->nullable();
            $table->string('view_mode', 20)->nullable();

            // ── Status / workflow ────────────────────────────────────────
            $table->string('status', 40)->nullable();
            $table->string('sub_status', 40)->nullable();
            $table->string('approval_status', 40)->nullable();
            $table->string('flag', 40)->nullable();

            // ── Search / text ────────────────────────────────────────────
            $table->string('search_query', 255)->nullable();
            $table->string('search_field', 60)->nullable();

            // ── Catch-all ────────────────────────────────────────────────
            $table->json('extra')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_filters');
    }
};