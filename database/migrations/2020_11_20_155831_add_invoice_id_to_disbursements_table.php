<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceIdToDisbursementsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('disbursements',
			function (Blueprint $table) {
				$table->string('invoice_id')->nullable()->after('paymentable_id');
			});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('disbursements',
			function (Blueprint $table) {
				$table->dropColumn('invoice_id');
			});
	}
}
