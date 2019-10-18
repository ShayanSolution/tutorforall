<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('tutor_id');
            $table->string('path');
            $table->smallInteger('status')->default(2);
            /**
             * status field value meaning
             *    2   by default (awaiting verification),
             *    1   accepted
             *    0   rejected
             **/
            $table->dateTime('verified_at')->nullable();
            $table->integer('verified_by')->nullable();
            $table->text('rejection_reason')->nullable();
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
        Schema::dropIfExists('documents');
    }
}
