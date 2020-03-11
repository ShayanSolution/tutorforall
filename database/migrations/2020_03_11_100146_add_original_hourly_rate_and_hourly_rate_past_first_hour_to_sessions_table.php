<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOriginalHourlyRateAndHourlyRatePastFirstHourToSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->float('original_hourly_rate')->after('hourly_rate');
            $table->float('hourly_rate_past_first_hour')->after('original_hourly_rate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('original_hourly_rate');
            $table->dropColumn('hourly_rate_past_first_hour');
        });
    }
}
