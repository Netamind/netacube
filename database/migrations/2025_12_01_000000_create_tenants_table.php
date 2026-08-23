<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No FOREIGN_KEY_CHECKS toggling needed: this migration is timestamped
        // to run after users, subscription_plans, and currency, so both FK
        // targets already exist when this table is created.
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Contact / identity
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('physical_address')->nullable();
            $table->string('postal_address')->nullable();

            // Business
            $table->string('business_name');

            // Tenant provisioning
            $table->string('client_url')->unique()->nullable();
            $table->string('data')->nullable(); // tenant database name
            $table->string('db_user')->nullable(); // cPanel/db username, production only

            // Approval workflow
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('approved_at')->nullable();

            // Hold / suspension
            $table->enum('put_on_hold', ['Yes', 'No'])->default('No');

            // Subscription & billing
            $table->unsignedBigInteger('subscription_plan')->nullable();
            $table->integer('payment_amount')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->date('next_payment_date')->nullable();

            // Custom pricing — lets a tenant be invoiced with their own
            // amount/currency (and optionally their own billing cycle)
            // instead of inheriting straight from their subscription plan.
            // The plan itself (name/description) is unaffected either way —
            // these only ever override the money and, optionally, the period.
            $table->boolean('custom_pricing_enabled')->default(false);
            $table->decimal('custom_amount', 12, 2)->nullable();
            $table->char('custom_currency', 3)->nullable();
            $table->unsignedInteger('custom_period_days')->nullable();
            $table->string('custom_period_name')->nullable();

            // Migration / table provisioning tracking
            $table->unsignedInteger('number_of_tables')->default(0);
            $table->enum('migration_status', ['not_started', 'running', 'completed', 'failed'])
                  ->default('not_started');
            $table->timestamp('migrated_at')->nullable();
            $table->text('migration_error')->nullable();
            $table->unsignedInteger('migration_attempts')->default(0);

            $table->timestamps();

            // Foreign keys — both targets already exist at this point in the
            // migration order (users, subscription_plans, currency).
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('subscription_plan')->references('id')->on('subscription_plans')->nullOnDelete();
            $table->foreign('custom_currency')->references('code')->on('currency')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};