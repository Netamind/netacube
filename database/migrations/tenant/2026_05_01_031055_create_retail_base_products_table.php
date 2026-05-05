<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_base_products', function (Blueprint $table) {
            $table->id();

            // ── Identity ──────────────────────────────────────────────────
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('internal_code')->unique()->nullable();
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('supplier')->nullable();
            $table->string('country_of_origin', 2)->nullable();

            // ── Measurement ───────────────────────────────────────────────
            $table->string('unit_of_measure')->default('Each');
            $table->decimal('weight_kg',     10, 4)->nullable();
            $table->decimal('volume_litres', 10, 4)->nullable();

            // ── Default Pricing ───────────────────────────────────────────
            $table->decimal('default_selling_price', 15, 2)->nullable();
            $table->decimal('default_cost_price',    15, 2)->nullable();

            // ── Categorisation ────────────────────────────────────────────
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('image_path')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('internal_code');
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_base_products');
    }
};