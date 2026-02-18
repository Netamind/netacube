<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->text('description')->nullable();

            $table->json('plan')->default('{
                "plan_name": "Balanced plan",
                "plan_period": "1 Year",
                "plan_period_name": "Yearly",
                "plan_amount": "0.00",
                "plan_currency": "USD",
                "plan_description": "Netacube Subscription"
            }');

            $table->json('payment_method')->nullable()->default('{
                "method_type": null,
                "account_name": null,
                "account_number": null,
                "account_type": null,
                "account_branch": null
            }');

            $table->enum('status', ['Pending', 'Paid', 'Overdue', 'Cancelled'])->default('Pending');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invoices');
    }
};