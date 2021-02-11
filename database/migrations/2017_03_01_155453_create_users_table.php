<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('uid', 36)->unique();
            $table->string('device_token');
            $table->string('firstName', '100')->nullable();
            $table->string('lastName', '100')->nullable();
            $table->string('fathername');
            $table->string('cnic_no');
            $table->string('experience');
            $table->string('qualification');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('confirmed');
            $table->string('confirmation_code');
            $table->string('middleName', '50')->nullable();
            $table->string('username', '50')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('address')->nullable();
            $table->string('zipCode')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('city', '100')->nullable();
            $table->string('state', '100')->nullable();
            $table->string('country', '100')->nullable();
            $table->boolean('is_active');
            $table->string('profileImage')->nullable();
            $table->integer('role_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });
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
