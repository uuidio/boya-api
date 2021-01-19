<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalDiscountFeeToTradeDaySettleAccountDetailsTable extends Migration
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
            $table->decimal('total_discount_fee', 10, 2)->default(0)->comment('折扣金额');

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
