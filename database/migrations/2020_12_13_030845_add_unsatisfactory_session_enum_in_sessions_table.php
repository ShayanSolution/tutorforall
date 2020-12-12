<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnsatisfactorySessionEnumInSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sessions', function (Blueprint $table) {
            DB::statement("ALTER TABLE `sessions` CHANGE `status` `status` ENUM('booked','started','ended','reject','pending','expired', 'cancelled', 'unsatisfactory_session');");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            DB::statement("ALTER TABLE `sessions` CHANGE `status` `status` ENUM('booked','started','ended','reject','pending','expired', 'cancelled');");
        });
    }
}
