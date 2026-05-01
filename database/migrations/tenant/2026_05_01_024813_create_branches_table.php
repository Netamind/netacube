<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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

    // ── Tax / VAT Configuration ─────────────────────────────────
    // Does this branch operate under VAT / EIS at all?
    $table->boolean('eis_enabled')->default(false);

    // The branch's own TIN (some branches may be separately registered)
    // Falls back to company_info.tin_number if null
    $table->string('tin_number')->nullable();

    // MRA's site ID for this branch — assigned during EIS onboarding
    // One branch = one MRA site
    $table->string('mra_site_id')->nullable();

    // Is this branch VAT registered?
    $table->boolean('is_vat_registered')->default(false);

    // The tax office that covers this branch (e.g. "SWE" = Songwe)
    $table->string('mra_tax_office_code')->nullable();
    $table->string('mra_tax_office_name')->nullable();

    // JSON: tax rates activated for this branch by MRA
    // e.g. ["T", "EX", "FIN"] — comes from terminal activation response
    $table->json('activated_tax_rate_ids')->nullable();

    // Config version numbers — received from MRA during terminal activation
    // Must be stored and sent with every sale transaction
    $table->integer('mra_global_config_version')->default(0);
    $table->integer('mra_taxpayer_config_version')->default(0);

    // ── Business Registration Numbers ───────────────────────────
    $table->string('business_number')->nullable();   // company reg number
    $table->string('business_tin')->nullable();      // alias/alternate; use tin_number

    // ── Unique Constraint ───────────────────────────────────────
    $table->unique(['name', 'sector', 'category'], 'b_s_c_unique');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
