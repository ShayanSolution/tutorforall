<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransactionsInTutorInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tutor_invoices', function (Blueprint $table) {
            $table->string('transaction_ref_no')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('transaction_platform')->nullable();
            $table->string('transaction_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tutor_invoices', function (Blueprint $table) {
            $table->dropColumn('transaction_ref_no');
            $table->dropColumn('transaction_type');
            $table->dropColumn('transaction_platform');
            $table->dropColumn('transaction_status');
        });
    }
}
