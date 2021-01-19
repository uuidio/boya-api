<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200220ActivitiesTransmitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities_transmits', function (Blueprint $table) {
            //
            $table->timestamp('start_time')->comment('活动开始时间');
            $table->timestamp('end_time')->comment('活动结束时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activities_transmits', function (Blueprint $table) {
            //
        });
    }
}
