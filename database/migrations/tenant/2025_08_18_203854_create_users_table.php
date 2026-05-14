<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('profile_picture')->nullable();
            $table->date('dob')->nullable();
            $table->string('idtype')->nullable();
            $table->string('idnumber')->nullable();

            // Employment
            $table->string('role')->nullable();
            $table->string('branch')->nullable();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->integer('gross_salary')->nullable();
            $table->date('started_on')->nullable();
            $table->date('entered_on')->nullable();
            $table->string('active')->default('Yes');
            $table->string('employment_type')->default('Full-time'); 
            $table->date('contract_end_date')->nullable(); 
            $table->boolean('on_paye')->default(false);            

            // Address
            $table->string('home_address')->nullable();
            $table->string('current_residence')->nullable();


            // Banking
            $table->string('bank_name')->nullable();                 
            $table->string('bank_account_name')->nullable();         
            $table->string('bank_account_number')->nullable();       
            $table->string('bank_branch')->nullable();               
            $table->string('bank_account_type')->default('Savings'); 

            // Next of kin
            $table->string('nextofkin_name')->nullable();
            $table->string('nextofkin_relationship')->nullable();
            $table->string('nextofkin_physical_address')->nullable();
            $table->string('nextofkin_contact')->nullable();
        });

        DB::table('users')->insert([
            'name'     => 'Admin (Default)',
            'phone'    => '0000000000',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('1234'),
            'role'     => 'Admin',
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}