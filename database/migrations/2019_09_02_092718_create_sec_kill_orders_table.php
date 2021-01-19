<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSecKillOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sec_kill_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->comment('商品sku_id');
            $table->unsignedInteger('seckill_ap_id')->comment('活动id');
            $table->string('state', 10)->default('0')->comment('状态标识-1无效,0抢购成功付款资格,1已付款秒杀成功,2未付款');
            $table->string('payment_id', 30)->default(0)->comment('支付单号');
            $table->string('tid', 30)->default(0)->comment('订单号');
            $table->timestamps();

            $table->index('user_id');
            $table->index('goods_id');
            $table->index('sku_id');
            $table->index('seckill_ap_id');
            $table->index('payment_id');
            $table->index('tid');
            $table->index('created_at');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('sec_kill_orders') . "` comment '秒杀明细'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sec_kill_orders');
    }
}
