<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCreditCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->increments('id');
			$table->integer('user_id');
			$table->integer('is_default')->default(0);
			$table->string('brand_name');
			$table->string('name_on_card');
			$table->string('card_number');
			$table->string('token_id');
			$table->string('session_id');
			$table->string('agreement_id');
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
        Schema::dropIfExists('credit_cards');
    }
}
