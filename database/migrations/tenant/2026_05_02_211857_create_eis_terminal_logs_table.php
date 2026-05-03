<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ─────────────────────────────────────────────────────────────────────────
    //  EIS TERMINAL LOGS
    //
    //  Immutable audit log of every HTTP call made to MRA's EIS API.
    //  Written by EisService::writeLog() — never edited after insert.
    //
    //  One row per MRA API call, regardless of outcome.
    //  Sensitive values (JWT tokens, secret keys) are redacted before storage.
    // ─────────────────────────────────────────────────────────────────────────

    public function up(): void
    {
        Schema::create('eis_terminal_logs', function (Blueprint $table) {
            $table->id();

            // ── Who made the call ─────────────────────────────────────────
            // set null so logs survive terminal deletion (audit trail)
            $table->unsignedBigInteger('terminal_id')->nullable()->index();
            $table->foreign('terminal_id')
                  ->references('id')->on('branch_terminals')
                  ->onDelete('set null');

            $table->unsignedBigInteger('branch_id')->nullable()->index();

            // ── Request ───────────────────────────────────────────────────
            // Short name used in filters/badges: activate|confirm|get_config|ping|sale
            $table->string('endpoint', 60)->index();

            // Full URL called (no query string)
            $table->string('url', 500)->nullable();

            // GET | POST | PUT | DELETE
            $table->string('http_method', 10)->default('POST');

            // Sanitised request body (sensitive fields redacted). Null for GET.
            $table->json('request_payload')->nullable();

            // ── Response ──────────────────────────────────────────────────
            // Raw HTTP status code (200, 400, 500…). Null if ConnectException.
            $table->unsignedSmallInteger('http_status')->nullable();

            // MRA's own statusCode inside the JSON body.
            // 0 = success for most endpoints; 1 = success for activate.
            $table->integer('mra_status_code')->nullable();

            // MRA's human-readable remark field.
            $table->string('mra_remark', 500)->nullable();

            // Sanitised response body (JWT tokens truncated to 30 chars).
            $table->json('response_payload')->nullable();

            // ── Outcome ───────────────────────────────────────────────────
            // success — MRA accepted the request
            // failed  — MRA returned a business-logic rejection
            // error   — network error / timeout / unexpected exception
            $table->enum('outcome', ['success', 'failed', 'error'])->index();

            // Internal description shown in the log detail drawer.
            $table->string('outcome_message', 500)->nullable();

            // Wall-clock duration of the HTTP call in milliseconds.
            $table->unsignedInteger('duration_ms')->nullable();

            // ── Trigger ───────────────────────────────────────────────────
            // manual    — operator clicked a button in the UI
            // scheduled — cron job (6-hourly config sync)
            // reactive  — triggered by sale response (shouldDownloadLatestConfig=true)
            $table->enum('trigger_source', ['manual', 'scheduled', 'reactive'])
                  ->default('manual')
                  ->index();

            // Null for scheduled / reactive triggers
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();

            $table->timestamps();  // created_at = when the API call was made

            // ── Composite indexes for common query patterns ───────────────
            $table->index(['terminal_id', 'created_at'],  'etl_terminal_time_idx');
            $table->index(['terminal_id', 'outcome'],     'etl_terminal_outcome_idx');
            $table->index(['terminal_id', 'endpoint'],    'etl_terminal_endpoint_idx');
            $table->index(['branch_id',   'created_at'],  'etl_branch_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eis_terminal_logs');
    }
};