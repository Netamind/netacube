<?php
// database/migrations/tenant/2026_07_17_000000_create_sales_dashboard_settings_table.php
//
// Per-user Sales dashboard settings — same shape as operations_dashboard_settings
// (one row per user_id, not a tenant-wide singleton like admin_dashboard_settings).
// Consumed by:
//   - SalesDashboardSettingsController (showSettingsView / updateSettings / defaultsObject)
//   - EnforceIdleTimeout + EnforceSessionLifetime, via
//     Concerns\ResolvesDashboardRoleSettings's 'Sales' role config
//
// Place alongside wherever the operations_dashboard_settings migration lives
// (it runs against the 'tenant' connection, so it belongs in the same
// tenant-migrations path/queue as that one — adjust the path above if your
// tenant migrations live somewhere other than database/migrations/tenant).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('sales_dashboard_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();

            // Inert for now — Sales users are tied to a branch, not a
            // sector, so nothing reads this yet. Column (and the settings
            // form field) kept for parity with operations_dashboard_settings
            // and so a future branch-based landing feature doesn't need a
            // schema change — the form renders it disabled in the meantime.
            $table->string('default_landing_sector')->nullable();

            $table->boolean('idle_timeout_enabled')->default(true);
            $table->decimal('idle_timeout_minutes', 8, 1)->default(30);
            $table->decimal('session_lifetime_minutes', 8, 1)->default(90);

            // Not exposed on the settings form (matches Operations — see
            // dashboard-settings.blade.php), but read directly by
            // EnforceIdleTimeout's single-session check.
            $table->boolean('enforce_single_session')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('sales_dashboard_settings');
    }
};