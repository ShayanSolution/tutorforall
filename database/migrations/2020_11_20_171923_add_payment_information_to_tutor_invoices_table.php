<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentInformationToTutorInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('tutor_invoices',
			function (Blueprint $table) {
				$table->float('commission')->default(0)->after('amount');
				$table->float('payable')->default(0)->after('commission');
				$table->float('receiveable')->default(0)->after('payable');
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
				$table->dropColumn('commission');
				$table->dropColumn('payable');
				$table->dropColumn('receiveable');
			});
	}
}
