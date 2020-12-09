<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreditCardsInTutorInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('tutor_invoices',
			function (Blueprint $table) {
				$table->string('transaction_session_id')->nullable()->after('transaction_status');
				$table->string('credit_card_id')->nullable()->after('transaction_session_id');
			});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('tutor_invoices',
			function (Blueprint $table) {
				$table->dropColumn('transaction_session_id');
				$table->dropColumn('credit_card_id');
			});
	}
}
