<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeSplitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_splits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->comment('商品sku_id');
            $table->unsignedInteger('quantity')->comment('购买数量');
            $table->string('payment_id', 100)->comment('支付单号');
            $table->string('tid', 60)->comment('订单号');
            $table->string('oid', 60)->comment('子订单单号');
            $table->text('coupon_shop_info')->nullable()->comment('店铺优惠券明细');
            $table->decimal('coupon_shop_fee', 10, 2)->default(0)->comment('店铺优惠券金额');
            $table->text('coupon_platform_info')->nullable()->comment('平台优惠券明细');
            $table->decimal('coupon_platform_fee', 10, 2)->default(0)->comment('平台优惠金额');
            $table->text('promotion_info')->nullable()->comment('促销信息');
            $table->decimal('promotion_fee', 10, 2)->default(0)->comment('促销金额');
            $table->decimal('points', 10, 2)->default(0)->comment('积分');
            $table->decimal('points_fee', 10, 2)->default(0)->comment('积分抵扣金额');
            $table->decimal('total_fee', 10, 2)->default(0)->comment('总金额');
            $table->decimal('payed', 10, 2)->default(0)->comment('实付');
            $table->string('pay_type', 30)->nullable()->comment('支付方式');
            $table->timestamps();

            $table->index('payment_id');
            $table->index('tid');
            $table->index('user_id');
            $table->index('goods_id');
            $table->index('sku_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('trade_splits') . "` comment '商品拆分明细表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_splits');
    }
}
