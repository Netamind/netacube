<?php
// 2026_07_16_000001_create_admin_dashboard_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_dashboard_settings', function (Blueprint $table) {
            $table->id();

            // Per-user, not tenant-wide: every Admin gets their own row,
            // created on first settings submit (see
            // AdminDashboardSettingsController::updateSettings). No row yet
            // = user is on defaults (see that controller's defaults()).
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique('user_id');

            // Landing / navigation
            $table->string('default_landing_sector')->nullable();
            $table->boolean('allow_sector_switching')->default(true);

            // Session / security
            $table->boolean('idle_timeout_enabled')->default(true);
            $table->unsignedInteger('idle_timeout_minutes')->default(60);
            $table->unsignedInteger('session_lifetime_minutes')->default(120);
            $table->boolean('enforce_single_session')->default(false);

            // Dashboard behaviour
            $table->unsignedInteger('dashboard_refresh_interval_seconds')->default(300);
            $table->enum('default_report_range', ['today', 'this_week', 'this_month', 'this_year'])->default('today');

            // Alerts
            $table->boolean('low_stock_alert_enabled')->default(true);
            $table->unsignedInteger('low_stock_alert_threshold')->default(10);
            $table->boolean('email_notifications_enabled')->default(true);
            $table->string('notification_email')->nullable();

            $table->timestamps();

            $table->index('default_landing_sector', 'ads_default_landing_sector_idx');
        });

        // No seed row — same as operations_dashboard_settings /
        // sales_dashboard_settings. Rows are created lazily per-user on
        // first settings submit. See AdminDashboardSettingsController::defaults()
        // for the values a user is on before their first save.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_dashboard_settings');
    }
};