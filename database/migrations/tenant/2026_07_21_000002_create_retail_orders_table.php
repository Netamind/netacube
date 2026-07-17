<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_orders', function (Blueprint $table) {
            $table->id();

            $table->date('date');

            $table->unsignedBigInteger('branch_id');
            $table->enum('category', ['Regular', 'Emergency', 'Rare']);

            $table->unsignedBigInteger('supplier_id')->nullable();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->boolean('is_custom')->default(false);

            $table->string('product_name');
            $table->string('units')->nullable();

            $table->string('quantity', 60)->default('0');

            $table->decimal('price', 12, 2)->default(0);

            $table->decimal('stock_at_order', 12, 2)->nullable();

            $table->enum('status', ['pending', 'ordered', 'received', 'cancelled'])
                  ->default('pending');

            $table->unsignedBigInteger('ordered_by');
            $table->text('remarks')->nullable();

            $table->string('client_uuid', 64)->nullable();
            $table->string('device_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('synced_offline')->default(false);

            $table->timestamps();

            $table->index(['branch_id', 'date']);
            $table->index(['category']);
            $table->index(['status']);
            $table->index(['product_id']);
            $table->index(['supplier_id']);
            $table->unique(['branch_id', 'client_uuid']);

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('ordered_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('retail_base_products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_orders');
    }
};