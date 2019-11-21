<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePeakFactorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('peak_factors', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('class_id');
            $table->integer('subject_id');
            $table->integer('is_group');
            $table->integer('category_id');
            $table->integer('experience');
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
        Schema::dropIfExists('peak_factors');
    }
}
