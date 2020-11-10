<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('session_id');
            $table->string('transaction_ref_no')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('transaction_platform');
            $table->string('amount')->nullable();
            $table->string('insert_date_time');
            $table->string('transaction_status')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('cnic_last_six_digits')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('session_payments');
    }
}
