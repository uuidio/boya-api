<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountFieldToTradeDaySettleAccountDetailsTable extends Migration
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
            $table->decimal('coupon_shop_fee', 10, 2)->default(0)->comment('店铺优惠券金额');
            $table->decimal('promotion_fee', 10, 2)->default(0)->comment('促销金额');
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
