<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->string('business_name');
            $table->string('client_url')->unique();
            $table->string('status')->default('Pending');
            $table->timestamp('approved_at')->nullable();
            $table->string('data')->nullable(); 
            $table->string('put_on_hold')->nullable()->default('No');
            $table->string('physical_address')->nullable();
            $table->string('postal_address')->nullable();
            $table->integer('approved_by')->nullable();
            $table->string('subscription_plan')->nullable();
            $table->integer('payment_amount')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('next_payment_date')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}