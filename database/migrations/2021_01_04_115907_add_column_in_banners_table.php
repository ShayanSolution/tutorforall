<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnInBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('send_to_csv')->after('storage_path');
            $table->integer('created_by')->after('storage_path');
            $table->dropColumn('user_id');
            $table->dropColumn('is_read');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('send_to_csv');
            $table->dropColumn('created_by');
            $table->integer('user_id')->nullable();
            $table->integer('is_read')->default(0);;
        });
    }
}
