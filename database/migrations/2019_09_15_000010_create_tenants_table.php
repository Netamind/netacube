<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Disabled so this table can be created (and reference subscription_plans /
        // currency via FK) regardless of migration run order — MySQL normally
        // refuses to add a foreign key to a table that doesn't exist yet.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

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

            // Foreign keys
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('subscription_plan')->references('id')->on('subscription_plans')->nullOnDelete();

            // NOTE: requires the `currency` table to already exist at
            // migration time — same ordering requirement as subscription_plan
            // above. Keep the currency migration's timestamp earlier than
            // this file's if you're setting up a fresh database.
            $table->foreign('custom_currency')->references('code')->on('currency')->nullOnDelete();
        });

        // Restore normal enforcement for every migration that runs after this one.
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('tenants');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};