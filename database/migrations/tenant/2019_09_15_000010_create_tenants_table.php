<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->string('payment_method')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->date('next_payment_date')->nullable();

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
        });
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('tenants');
    }
};