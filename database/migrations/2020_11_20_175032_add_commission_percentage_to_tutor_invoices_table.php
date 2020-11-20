<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommissionPercentageToTutorInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('tutor_invoices',
			function (Blueprint $table) {
				$table->float('commission_percentage')->nullable()->after('transaction_status');
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
				$table->dropColumn('commission_percentage');
			});
	}
}
