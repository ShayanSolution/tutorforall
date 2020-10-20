<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCancelledInSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sessions', function (Blueprint $table) {
            DB::statement("ALTER TABLE `sessions` CHANGE `status` `status` ENUM('booked','started','ended','reject','pending','expired', 'cancelled');");
            $table->integer('cancelled_by')->nullable();
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
            DB::statement("ALTER TABLE `sessions` CHANGE `status` `status` ENUM('booked','started','ended','reject','pending','expired');");
            $table->dropColumn('cancelled_by');
        });
    }
}
