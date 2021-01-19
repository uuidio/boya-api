<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeDaySettleAccountDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_day_settle_account_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tid',60)->comment('订单编号');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->timestamp('pay_time')->nullable()->comment('付款时间');
            $table->string('pay_type',20)->nullable()->comment('支付方式');
            $table->decimal('goods_price_amount', 10, 2)->default(0)->comment('商品总金额');
            $table->decimal('shop_act_fee',10,2)->default(0)->comment('店铺优惠,活动优惠+优惠劵');
            $table->decimal('platform_act_fee',10,2)->default(0)->comment('平台优惠,平台卷');
            $table->decimal('points_fee',10,2)->default(0)->comment('积分抵扣金额');
            $table->decimal('points',10,2)->default(0)->comment('使用积分');
            $table->decimal('post_fee', 10, 2)->default(0)->comment('邮费');
            $table->decimal('payed',10,2)->default(0)->comment('实付金额');
            $table->string('shop_rate',20)->nullable()->comment('店铺扣点比例');
            $table->string('point_rate',100)->nullable()->comment('积分比例,平台');
            $table->decimal('shop_rate_fee', 10, 2)->default(0)->comment('店铺扣点金额');
            $table->decimal('refund_fee', 10, 2)->default(0)->comment('退款金额');
            $table->unsignedInteger('refund_type')->default(1)->comment('退款方式,1-线上退款,2-线下');
            $table->decimal('settlement_fee',10,2)->nullable()->comment('结算金额');
            $table->unsignedInteger('settle_type')->default(1)->comment('结算类型,1-普通结算,2-退货结算');
            $table->timestamp('settle_time')->nullable()->comment('账单结算时间');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_day_settle_account_details') . "` comment '日结订单数据表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_day_settle_account_details');
    }
}
