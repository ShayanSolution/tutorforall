<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('session_id')->nullable()->change();
            $table->string('from_user_id')->nullable()->change()->comment("This will be always student id and null when admin credit/debit to tutor");
            $table->string('to_user_id')->nullable()->change()->comment("This will be always tutor id and null when admin credit/debit to student");
            $table->integer('added_by')->nullable();
            $table->string('admin_user_name')->nullable();
            $table->text('reason_from_admin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wallets', function (Blueprint $table) {
            //
        });
    }
}
