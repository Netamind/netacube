<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ─────────────────────────────────────────────────────────────────────────
    //  EIS OFFLINE QUEUE
    //
    //  Holds sale transactions that were issued while MRA was unreachable.
    //  MRA allows offline sales up to a configured time limit and cumulative
    //  amount (from offlineLimit in the terminal activation response).
    //
    //  When a sale is issued offline:
    //    1. A QR code is generated locally using the terminal's secretKey.
    //    2. The full invoice payload is stored here with status='pending'.
    //    3. When connectivity returns, EisSalesController (future) flushes
    //       pending rows in chronological order by calling MRA's sale endpoint.
    //    4. On successful submission, the row is marked 'submitted'.
    //    5. On repeated failure, the row is marked 'failed' and the operator
    //       is alerted in the EIS dashboard.
    //
    //  This table is created now so the schema is in place, but the
    //  flush logic lives in EisSalesController (not yet implemented).
    // ─────────────────────────────────────────────────────────────────────────

    public function up(): void
    {
        Schema::connection('tenant')->create('eis_offline_queue', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('terminal_id');
            $table->foreign('terminal_id')
                ->references('id')->on('branch_terminals')
                ->onDelete('cascade');

            $table->unsignedBigInteger('branch_id');

            // ── Invoice Identity ──────────────────────────────────────────
            // The generated invoice number (Base64 encoded components).
            // Must be unique per terminal per day.
            $table->string('invoice_number', 100);

            // Date and time the receipt was issued to the customer
            $table->datetime('invoice_date_time');

            // ── Payload ───────────────────────────────────────────────────
            // The full JSON payload that will be sent to MRA's sale endpoint
            // once connectivity is restored. Built at point of sale.
            $table->json('payload');

            // ── Offline Signature ─────────────────────────────────────────
            // HMAC-SHA512 signature generated locally using the terminal's
            // secretKey. This goes into invoiceSummary.offlineSignature when
            // the payload is eventually submitted to MRA.
            // Also used to generate the QR code on the offline receipt.
            $table->text('offline_signature')->nullable();

            // ── Status Tracking ───────────────────────────────────────────
            // 'pending'   — not yet submitted to MRA
            // 'submitted' — MRA accepted the transaction
            // 'failed'    — MRA rejected after max retry attempts
            $table->enum('status', ['pending', 'submitted', 'failed'])->default('pending');

            // When it was successfully submitted to MRA
            $table->timestamp('submitted_at')->nullable();

            // MRA's validationURL returned after successful submission
            $table->string('mra_validation_url', 500)->nullable();

            // ── Retry Tracking ────────────────────────────────────────────
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->text('last_error')->nullable();

            // Counters used in invoice number generation (stored for resubmission)
            $table->integer('daily_count_at_issue')->default(1);

            $table->timestamps();

            $table->unique(['terminal_id', 'invoice_number'], 'eq_terminal_invoice_unique');
            $table->index(['terminal_id', 'status']);
            $table->index(['branch_id',   'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('eis_offline_queue');
    }
};