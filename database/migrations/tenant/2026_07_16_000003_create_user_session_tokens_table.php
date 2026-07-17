<?php
// 2026_07_16_000003_create_user_session_tokens_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per user. Every fresh login overwrites this row with a new
     * random token and stashes the same token in the PHP session. If
     * enforce_single_session is on for the user's role, EnforceIdleTimeout
     * compares the two on every request — a mismatch means someone logged
     * into the same account elsewhere, so the older session is kicked out.
     */
    public function up(): void
    {
        Schema::create('user_session_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('session_token', 64);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('session_token', 'ust_session_token_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_session_tokens');
    }
};