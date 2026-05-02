<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ─────────────────────────────────────────────────────────────────────────
    //  EIS GLOBAL CONFIG
    //
    //  Stores the globalConfiguration received from MRA via the
    //  getLatestConfig endpoint. This is the same for every terminal on
    //  every branch — it is MRA-wide config (tax rates, levies, etc.).
    //
    //  We keep ONE active row (id = 1). When MRA pushes a new version,
    //  we UPDATE that row in place and archive the old one in eis_config_log.
    //
    //  This table is READ-ONLY from the operations UI — users can view it
    //  and trigger a manual sync, but cannot edit it directly.
    //
    //  Syncing happens:
    //    • Manually: operator clicks "Sync now" in the EIS settings UI.
    //    • Automatically: scheduled command runs every 6 hours.
    //    • Reactively: whenever a sale response has shouldDownloadLatestConfig=true
    //      (handled in EisSalesController — out of scope for now).
    // ─────────────────────────────────────────────────────────────────────────

    public function up(): void
    {
        Schema::connection('tenant')->create('eis_global_config', function (Blueprint $table) {
            $table->id();

            // The MRA version number for this global config.
            // MRA increments this when tax rates or levies change.
            // We use it to decide whether a sync is needed.
            $table->integer('mra_version_no')->default(0);

            // ── Tax Rates ──────────────────────────────────────────────────
            // Full array of tax rate objects from MRA globalConfiguration.taxrates.
            // Each object: { id, name, chargeMode, ordinal, rate }
            // e.g. [{"id":"T","name":"VAT","chargeMode":"Item","ordinal":100,"rate":16.5}]
            // chargeMode: "Item" = per line item, "Global" = applied to invoice total
            $table->json('tax_rates')->nullable();

            // ── Levies ────────────────────────────────────────────────────
            // Levies from taxpayerConfiguration.activatedLevies (per activation response).
            // Only present if the taxpayer has active levies (e.g. Tourism Levy).
            // Each object: { id, name, chargeMode, rate, isActive }
            $table->json('activated_levies')->nullable();

            // ── Sync Tracking ─────────────────────────────────────────────
            // Which terminal was used to do the last sync.
            // Any activated terminal for any branch can be used — the global
            // config is the same regardless of which terminal fetches it.
            $table->unsignedBigInteger('synced_via_terminal_id')->nullable();

            // Last successful sync from MRA
            $table->timestamp('last_synced_at')->nullable();

            // Last sync attempt (whether it succeeded or not)
            $table->timestamp('last_sync_attempted_at')->nullable();

            // 'ok' | 'failed' | 'never'
            $table->string('last_sync_status', 20)->default('never');

            // Error message from the last failed sync attempt
            $table->text('last_sync_error')->nullable();

            $table->timestamps();
        });

        // Seed the single config row so it always exists.
        // The operations code uses updateOrInsert on id=1 rather than insert().
        DB::connection('tenant')->table('eis_global_config')->insert([
            'id'                       => 1,
            'mra_version_no'           => 0,
            'tax_rates'                => null,
            'activated_levies'         => null,
            'synced_via_terminal_id'   => null,
            'last_synced_at'           => null,
            'last_sync_attempted_at'   => null,
            'last_sync_status'         => 'never',
            'last_sync_error'          => null,
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('eis_global_config');
    }
};