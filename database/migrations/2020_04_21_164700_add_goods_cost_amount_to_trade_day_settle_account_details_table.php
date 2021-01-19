<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGoodsCostAmountToTradeDaySettleAccountDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_day_settle_account_details', function (Blueprint $table) {
            //
            $table->decimal('goods_cost_amount', 10, 2)->default(0)->comment('成本价汇总');
            $table->decimal('profit_amount', 10, 2)->default(0)->comment('利润额汇总');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_day_settle_account_details', function (Blueprint $table) {
            //
        });
    }
}
