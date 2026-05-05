<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            // ── Identity ──────────────────────────────────────────────────
            $table->string('name');
            $table->string('trading_name')->nullable();
            $table->string('registration_number')->nullable();

            // ── Contact ───────────────────────────────────────────────────
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_alt')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // ── Bank & Payment ────────────────────────────────────────────
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_swift_code')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('currency')->nullable()->default('MWK');

            // ── Address ───────────────────────────────────────────────────
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable()->default('Malawi');

            // ── Classification ────────────────────────────────────────────
            $table->unsignedBigInteger('category');
            $table->string('sector');          

            // ── Status ────────────────────────────────────────────────────
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');

            $table->text('notes')->nullable();

            $table->unique('name', 'suppliers_name_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};