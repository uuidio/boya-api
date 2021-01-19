<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeMonthSettleAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_month_settle_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->unsignedInteger('tradecount')->default(0)->comment('订单数量');
            $table->decimal('goods_price_amount', 10, 2)->default(0)->comment('商品总金额');
            $table->decimal('shop_act_fee_amount',10,2)->default(0)->comment('店铺优惠,活动优惠+优惠劵');
            $table->decimal('platform_act_fee_amount',10,2)->default(0)->comment('平台优惠,平台卷');
            $table->decimal('points_fee_amount', 10, 2)->default(0)->comment('积分抵扣金额');
            $table->decimal('points_amount', 10, 2)->default(0)->comment('使用积分');
            $table->decimal('post_fee_amount', 10, 2)->default(0)->comment('邮费');
            $table->decimal('payed_amount',10,2)->default(0)->comment('实付金额');
            $table->string('shop_rate',20)->nullable()->comment('店铺扣点比例');
            $table->decimal('shop_rate_fee_amount', 10, 2)->default(0)->comment('店铺扣点金额');
            $table->decimal('refund_fee_amount', 10, 2)->default(0)->comment('退款金额');
            $table->decimal('manage_fee', 10, 2)->default(0)->comment('管理费费');
            $table->string('point_rate',100)->nullable()->comment('积分比例');
            $table->string('settlement_fee_amount',100)->nullable()->comment('结算金额');
            $table->unsignedInteger('status')->default(0)->comment('结算状态,0-未结算,1-已结算');
            $table->timestamp('settle_time')->nullable()->comment('账单结算时间');

            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('trade_month_settle_accounts') . "` comment '月结数据表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_month_settle_accounts');
    }
}
