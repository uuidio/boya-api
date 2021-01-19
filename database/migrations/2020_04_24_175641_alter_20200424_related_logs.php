<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200424RelatedLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('related_logs', function (Blueprint $table) {
            //
            $table->smallInteger('hold')->default(0)->comment('1-保留,0-不保留');

            $table->index('hold');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('related_logs', function (Blueprint $table) {
            //
        });
    }
}
