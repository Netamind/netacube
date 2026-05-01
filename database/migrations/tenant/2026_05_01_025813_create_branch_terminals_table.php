<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
    Schema::create('branch_terminals', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('branch_id');
    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

    // Human label for this till, e.g. "Till-01", "Counter-2"
    $table->string('terminal_label');

    // The TAC entered by the taxpayer to activate this terminal
    // After activation this is no longer valid but keep for audit
    $table->string('terminal_activation_code')->nullable();

    // MRA's UUID for this terminal — returned on activation
    $table->string('mra_terminal_id')->nullable()->unique();

    // Sequential position number within the branch/site
    // Used in invoice number generation: Base64(terminalPosition)
    $table->integer('terminal_position')->default(1);

    // ── Credentials from MRA Activation Response ────────────────
    $table->text('mra_jwt_token')->nullable();       // bearer token for API calls
    $table->string('mra_secret_key')->nullable();    // for offline QR signing

    // ── Config Versions (per terminal) ──────────────────────────
    $table->integer('mra_terminal_config_version')->default(0);

    // Daily transaction counter — used in invoice number generation
    // Reset to 0 each day
    $table->integer('daily_transaction_count')->default(0);
    $table->date('transaction_count_date')->nullable(); // the date for the count above

    // ── Activation Status ────────────────────────────────────────
    $table->enum('activation_status', [
        'pending',      // TAC entered, not yet sent to MRA
        'activated',    // MRA confirmed activation
        'failed',       // activation attempt failed
        'deactivated',  // retired terminal
    ])->default('pending');

    $table->timestamp('activated_at')->nullable();

    // ── Offline Mode Config ──────────────────────────────────────
    // From MRA activation response: offlineLimit
    $table->integer('offline_max_hours')->default(0);
    $table->decimal('offline_max_cumulative_amount', 15, 2)->default(0);

    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamps();

    $table->unique(['branch_id', 'terminal_label'], 'branch_terminal_unique');
   });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_terminals');
    }
};
