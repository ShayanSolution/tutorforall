<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisbursementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disbursements', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tutor_id');
            $table->string('type');
            $table->float('amount');
            $table->string('paymentable_type');
            $table->integer('paymentable_id');
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
        Schema::dropIfExists('disbursements');
    }
}
