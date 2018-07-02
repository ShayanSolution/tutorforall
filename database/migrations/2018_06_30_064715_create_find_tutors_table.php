<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFindTutorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('find_tutors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('student_id');
            $table->integer('class_id');
            $table->integer('subject_id');
            $table->integer('is_group');
            $table->string('longitude');
            $table->string('latitude');
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('find_tutors');
    }
}
