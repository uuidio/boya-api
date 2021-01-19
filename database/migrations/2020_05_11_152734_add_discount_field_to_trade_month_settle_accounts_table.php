<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountFieldToTradeMonthSettleAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_month_settle_accounts', function (Blueprint $table) {
            //

            $table->decimal('coupon_shop_fee_amount', 10, 2)->default(0)->comment('店铺优惠券金额汇总');
            $table->decimal('promotion_fee_amount', 10, 2)->default(0)->comment('促销金额汇总');
            $table->decimal('total_discount_fee_amount', 10, 2)->default(0)->comment('折扣金额汇总');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_month_settle_accounts', function (Blueprint $table) {
            //
        });
    }
}
