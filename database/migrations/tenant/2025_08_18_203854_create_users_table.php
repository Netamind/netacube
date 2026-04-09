<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->date('dob')->nullable();
            $table->string('branch')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('idtype')->nullable();
            $table->string('idnumber')->nullable();
            $table->date('started_on')->nullable();
            $table->string('home_address')->nullable();
            $table->string('current_residence')->nullable();
            $table->date('entered_on')->nullable();
            $table->string('active')->default('Yes');
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->integer('gross_salary')->nullable();
            $table->string('role')->nullable();
            $table->string('password')->nullable();
            $table->string('nextofkin_name')->nullable();
            $table->string('nextofkin_relationship')->nullable();
            $table->string('nextofkin_physical_address')->nullable();
            $table->string('nextofkin_contact')->nullable();
            });
        DB::table('users')->insert([
            'name' => 'Default Admin (Super)',
            'phone' => '0000000000',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('1234'),
            'role' => 'Admin',
        ]);    
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
