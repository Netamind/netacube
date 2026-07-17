<?php
// FILE: database/migrations/2026_07_15_000000_create_retail_partialstocktaking_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * One row per (branch, date, device) that has opened a Partial Stocktaking
 * counting session. max_sales_id_at_start is a server-minted ceiling — the
 * highest retail_system_sales.id that already existed the moment this
 * device's session was opened, captured server-side so the counter never
 * gets to choose their own checkpoint (unlike a client-reported timestamp).
 *
 * The unique constraint is what makes "first session of the day wins" safe
 * under concurrent requests: RetailPartialstocktakingController::
 * startCountingSession() just tries the insert and catches the duplicate,
 * the same atomic-insert-is-the-lock pattern used elsewhere in this module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_partialstocktaking_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->date('date');
            $table->string('device_id', 120);
            $table->string('device_label', 120)->nullable();
            $table->unsignedBigInteger('max_sales_id_at_start')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'date', 'device_id'], 'pst_sessions_branch_date_device_unique');
            $table->index(['branch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_partialstocktaking_sessions');
    }
};