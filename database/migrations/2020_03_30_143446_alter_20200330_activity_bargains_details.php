<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200330ActivityBargainsDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_bargains_details', function (Blueprint $table) {
            //
            //
            $table->unsignedTinyInteger('stock_log')->default(0)->comment('库存记录');
            $table->unsignedTinyInteger('ap_id')->default(0)->comment('平台申请活动id');
            $table->index('ap_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_bargains_details', function (Blueprint $table) {
            //
        });
    }
}
