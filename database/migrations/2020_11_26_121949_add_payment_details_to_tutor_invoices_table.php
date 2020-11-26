<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentDetailsToTutorInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('tutor_invoices',
			function (Blueprint $table) {
				$table->float('cash_payment')->default(0);
				$table->float('jazzcash_payment')->default(0);
				$table->float('card_payment')->default(0);
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
				$table->dropColumn('cash_payment');
				$table->dropColumn('jazzcash_payment');
				$table->dropColumn('card_payment');
			});
	}
}
