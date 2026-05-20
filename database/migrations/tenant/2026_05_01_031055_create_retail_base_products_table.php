<?php
//baseproducts new system
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_base_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique()->nullable();
            $table->string('supplier');
            $table->string('unit')->default('Each');
            $table->decimal('cost_price',    15, 2)->nullable();
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->boolean('is_product')->default(true);
            $table->timestamps();
            $table->index('code');
            $table->index('is_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_base_products');
    }
};