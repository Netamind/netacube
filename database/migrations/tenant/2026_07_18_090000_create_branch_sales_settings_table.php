<?php
// File: database/migrations/2026_05_02_090000_create_branch_sales_settings_table.php
//branch-level sales dashboard + sales-session settings, one row per branch
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_sales_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('branch_id');

            // ── Cloud sales sync ─────────────────────────────────────────
            $table->boolean('auto_upload_cloud_sales')->default(false);
            $table->unsignedInteger('auto_upload_cloud_sales_interval_minutes')->nullable(); // e.g. 2 — only meaningful while auto_upload_cloud_sales is true
            $table->boolean('allow_to_clear_cloud_sales')->default(false);

            // ── Sales dashboard default-view widgets ─────────────────────
            $table->boolean('display_yesterdays_sales')->default(true);
            $table->boolean('display_price_changes')->default(true);
            $table->boolean('display_deliverynotes_this_month')->default(true);
            $table->boolean('display_sales_this_month')->default(true);
            $table->boolean('display_number_of_customers_today')->default(true);
            $table->boolean('display_regular_orders_short_cut')->default(true);
            $table->boolean('display_emergency_order_short_cut')->default(true);
            $table->boolean('display_low_stock_alerts')->default(false);

            // ── Page behaviour ────────────────────────────────────────────
            $table->boolean('auto_refresh_page')->default(false);
            $table->unsignedInteger('auto_refresh_interval_minutes')->nullable(); // only meaningful while auto_refresh_page is true

            // ── Session behaviour — null = disabled, a number (minutes) = enabled ──
            $table->unsignedInteger('idle_timeout_minutes')->nullable();
            $table->unsignedInteger('session_lifetime_minutes')->nullable();

            $table->unique('branch_id', 'branch_sales_settings_branch_unique');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_sales_settings');
    }
};