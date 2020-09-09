<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('place_id')->nullable();
            $table->string('place_name');
            $table->string('place_address')->nullable();
            $table->string('place_detail')->nullable();
            $table->string('saved_name')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->string('type');
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
        Schema::dropIfExists('search_locations');
    }
}
