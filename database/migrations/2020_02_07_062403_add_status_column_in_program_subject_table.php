<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColumnInProgramSubjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('program_subject', function (Blueprint $table) {
            $table->integer('document_id');
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
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program_subject', function (Blueprint $table) {
            $table->dropColumn('document_id');
            $table->dropColumn('status');
            $table->dropColumn('verified_at');
            $table->dropColumn('verified_by');
            $table->dropColumn('rejection_reason');
        });
    }
}
