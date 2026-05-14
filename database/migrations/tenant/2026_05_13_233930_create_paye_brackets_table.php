<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePayeBracketsTable extends Migration
{
    public function up()
    {
        Schema::create('paye_brackets', function (Blueprint $table) {
            $table->id();
            $table->decimal('income_from', 15, 2);
            $table->decimal('income_to',   15, 2)->nullable(); // null = no ceiling (top band)
            $table->decimal('rate',         5, 2);             // percentage e.g. 25.00
            $table->date('effective_from');
            $table->date('effective_to')->nullable();           // null = currently active
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // ── Seed current MRA Malawi monthly brackets (FY 2024/25) ─────────
        $now = now();
        DB::table('paye_brackets')->insert([
            [
                'income_from'    => 0,
                'income_to'      => 100000,
                'rate'           => 0.00,
                'effective_from' => '2024-07-01',
                'effective_to'   => null,
                'description'    => 'Tax-free personal allowance',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'income_from'    => 100000,
                'income_to'      => 1000000,
                'rate'           => 25.00,
                'effective_from' => '2024-07-01',
                'effective_to'   => null,
                'description'    => '25% band',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'income_from'    => 1000000,
                'income_to'      => 3000000,
                'rate'           => 30.00,
                'effective_from' => '2024-07-01',
                'effective_to'   => null,
                'description'    => '30% band',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'income_from'    => 3000000,
                'income_to'      => null,
                'rate'           => 35.00,
                'effective_from' => '2024-07-01',
                'effective_to'   => null,
                'description'    => '35% band (no ceiling)',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('paye_brackets');
    }
}