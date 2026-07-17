<?php
// 2026_07_16_000002_create_operations_dashboard_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_dashboard_settings', function (Blueprint $table) {
            $table->id();

            // Per-user, not tenant-wide: every Operations user gets their own
            // row, created on first settings submit (see
            // OperationsDashboardSettingsController::updateSettings). No row
            // yet = user is on defaults (see that controller's defaults()).
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique('user_id');

            // Session / security
            $table->boolean('idle_timeout_enabled')->default(true);
            // decimal, not unsignedInteger: allows fractional minutes (e.g.
            // 0.5 = 30 seconds) so timeouts can be tested in seconds instead
            // of waiting a full minute+ per test run.
            $table->decimal('idle_timeout_minutes', 6, 2)->default(30);
            $table->decimal('session_lifetime_minutes', 6, 2)->default(90);
            $table->boolean('enforce_single_session')->default(true);

            // Landing / navigation
            // If set (and the logged-in user actually has access to it),
            // Operations users skip the dashboard hub entirely and land
            // straight on that sector's dashboard.
            $table->string('default_landing_sector')->nullable();
            $table->boolean('allow_sector_switching')->default(false);
            $table->boolean('restrict_to_assigned_branch')->default(false);

            // Dashboard behaviour
            $table->unsignedInteger('dashboard_refresh_interval_seconds')->default(300);
            $table->enum('default_report_range', ['today', 'this_week', 'this_month', 'this_year'])->default('today');

            // Alerts
            $table->boolean('low_stock_alert_enabled')->default(true);
            $table->unsignedInteger('low_stock_alert_threshold')->default(10);

            $table->timestamps();
        });

        // No seed row — rows are created lazily per-user on first settings
        // submit. See OperationsDashboardSettingsController::defaults() for
        // the values a user is on before their first save.
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_dashboard_settings');
    }
};