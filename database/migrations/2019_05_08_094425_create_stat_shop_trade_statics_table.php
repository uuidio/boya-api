<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatShopTradeStaticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_shop_trade_statics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->unsignedInteger('new_trade')->default(0)->comment('新增订单数');
            $table->decimal('new_fee', 10, 2)->default(0)->comment('新增订单额');
            $table->unsignedInteger('ready_trade')->default(0)->comment('待付款订单数');
            $table->decimal('ready_fee', 10, 2)->default(0)->comment('待付款订单额');
            $table->unsignedInteger('alreadytrade')->default(0)->comment('已付款订单数');
            $table->decimal('alreadyfee', 10, 2)->default(0)->comment('已付款订单额');
            $table->unsignedInteger('ready_send_trade')->default(0)->comment('待发货订单数');
            $table->decimal('ready_send_fee', 10, 2)->default(0)->comment('待发货订单额');
            $table->unsignedInteger('already_send_trade')->default(0)->comment('待收货订单数');
            $table->decimal('already_send_fee', 10, 2)->default(0)->comment('待收货订单额');
            $table->unsignedInteger('cancle_trade')->default(0)->comment('已取消订单数');
            $table->decimal('cancle_fee', 10, 2)->default(0)->comment('已取消订单额');
            $table->unsignedInteger('complete_trade')->default(0)->comment('已完成订单数');
            $table->decimal('complete_fee', 10, 2)->default(0)->comment('已完成订单额');
            $table->unsignedInteger('refund_trade')->default(0)->comment('退货退款订单数');
            $table->decimal('refund_fee', 10, 2)->default(0)->comment('退货退款订单额');
            $table->unsignedInteger('reject_trade')->default(0)->comment('拒收退款订单数');
            $table->decimal('reject_fee', 10, 2)->default(0)->comment('拒收退款订单额');
            $table->unsignedInteger('changing_trade')->default(0)->comment('换货订单数');
            $table->decimal('total_refund_fee', 10, 2)->default(0)->comment('退款总金额');

            $table->timestamps();
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('stat_shop_trade_statics') . "` comment '商家交易统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_shop_trade_statics');
    }
}
