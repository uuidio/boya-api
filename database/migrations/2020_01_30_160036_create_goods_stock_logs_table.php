<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_stock_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->comment('商品id');
            $table->unsignedInteger('goods_stock')->comment('库存');
            $table->integer('change')->default(0)->comment('增减数量');
            $table->string('type', 10)->default('inc')->comment('类型（inc增加、dec扣减、add添加商品、edit编辑商品）');
            $table->string('oid',100)->nullable()->comment('子订单id');
            $table->string('note')->nullable()->comment('描述');
            $table->timestamps();

            $table->index('type');
            $table->index('shop_id');
            $table->index('goods_id');
            $table->index('sku_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('goods_stock_logs') . "` comment '商品库存变化记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_stock_logs');
    }
}
