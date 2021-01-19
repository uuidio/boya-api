<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20190907TradeOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_orders', function (Blueprint $table) {
            //
            $table->unsignedInteger('activity_sign')->default(0)->comment('活动标识,活动id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_orders', function (Blueprint $table) {
            //
        });
    }
}
