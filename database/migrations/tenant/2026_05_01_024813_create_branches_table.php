<?php
//branches schema new system
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            // ── Basic Info ──────────────────────────────────────────────
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('sector');
            $table->string('category');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            // ── Business Registration ───────────────────────────────────
            $table->string('business_number')->nullable();

            $table->unique(['name', 'sector', 'category'], 'b_s_c_unique');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};