<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {


      DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::create('retail_intervals', function (Blueprint $table) {
            $table->id();
            $table->string('slot', 50);          // e.g. "07:00AM-10:00AM"
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('slot');
        });

        // Seed the 9 fixed slots, in display order.
        $slots = [
            '07:00AM-10:00AM',
            '10:00AM-12:00PM',
            '12:00PM-02:00PM',
            '02:00PM-04:00PM',
            '04:00PM-06:00PM',
            '06:00PM-08:00PM',
            '08:00PM-10:00PM',
            '10:00PM-12:00AM',
            '12:00AM-07:00AM',
        ];

        $now = now();
        $rows = [];
        foreach ($slots as $i => $slot) {
            $rows[] = [
                'slot'       => $slot,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('retail_intervals')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('retail_intervals');
    }
};