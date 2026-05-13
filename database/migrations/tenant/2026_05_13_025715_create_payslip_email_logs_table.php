<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_email_logs', function (Blueprint $table) {
            $table->id();

            // ── References ────────────────────────────────────────────────
            $table->unsignedBigInteger('payroll_entry_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('payroll_period_id');

            // ── Delivery info ─────────────────────────────────────────────
            $table->string('recipient_email', 255);
            $table->enum('send_type', ['single', 'bulk'])->default('single');
            $table->enum('status', ['sent', 'failed', 'skipped'])->default('sent');

            // ── Optional content ──────────────────────────────────────────
            $table->text('note')->nullable();
            $table->string('sent_by', 255)->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────
            $table->index('payroll_entry_id');
            $table->index('employee_id');
            $table->index('payroll_period_id');
            $table->index('status');
            $table->index('send_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_email_logs');
    }
};