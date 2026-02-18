<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->enum('method_type', ['Bank', 'Mobile', 'Paypal'])->default('Bank');
    
            // Bank Transfer
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_type')->nullable();     
            $table->string('account_branch')->nullable();
            $table->string('account_swift_code')->nullable();

            // Mobile Money
            $table->string('mobile_operator')->nullable();      
            $table->string('mobile_number')->nullable();        
            $table->string('mobile_number_name')->nullable();   

            // PayPal
            $table->string('paypal_name')->nullable();          
            $table->string('paypal_email')->nullable();         
            $table->string('paypal_me_link')->nullable();       

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();


          
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};