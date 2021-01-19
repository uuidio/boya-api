<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSecKillStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sec_kill_stock_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('seckill_ap_id')->comment('秒杀活动id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->comment('商品id');
            $table->unsignedInteger('goods_stock')->comment('库存');
            $table->string('type', 10)->default('inc')->comment('类型（inc添加dec扣减）');
            $table->string('note')->nullable()->comment('描述');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('seckill_ap_id');
            $table->index('goods_id');
            $table->index('sku_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('sec_kill_stock_logs') . "` comment '秒杀商品库存变化记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sec_kill_stock_logs');
    }
}
