<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAverageFeeAmountToTradeDaySettleAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_day_settle_accounts', function (Blueprint $table) {
            //
            $table->decimal('average_fee_amount', 10, 2)->default(0)->comment('平均成交金额(客单价)');
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
        Schema::table('trade_day_settle_accounts', function (Blueprint $table) {
            //
        });
    }
}
