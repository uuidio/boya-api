<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200416TradeOrders extends Migration
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
            $table->decimal('rewards', 10, 2)->default(0)->comment('返利金额');
            $table->decimal('profit_sharing', 10, 2)->default(0)->comment('分成金额');
            $table->smallInteger('profit_sign')->default(0)->comment('分成订单状态,0-否,1-未分成,2-已分成');
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
