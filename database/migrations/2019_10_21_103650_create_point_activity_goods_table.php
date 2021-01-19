<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointActivityGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_activity_goods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('goods_name', 150)->nullable()->comment('商品名称');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->nullable()->comment('skuId');
            $table->decimal('goods_price', 10, 2)->comment('店铺价格');
            $table->decimal('point_price', 10, 2)->comment('积分专区价格');
            $table->unsignedInteger('point_fee')->default(0)->comment('需要积分');
            $table->string('goods_image')->nullable()->comment('商品图片');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('goods_id');
            $table->index('sku_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('point_activity_goods') . "` comment '积分专区商品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_activity_goods');
    }
}
