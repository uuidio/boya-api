<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatShopItemStaticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_shop_item_statics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->unsignedInteger('goods_id')->default(0)->comment('商品id');
            $table->string('title',100)->nullable()->comment('商品标题');
            $table->string('pic_path',200)->nullable()->comment('商品图片');
            $table->unsignedInteger('amountnum')->default(0)->comment('销售数量');
            $table->decimal('amountprice', 10, 2)->default(0)->comment('销售总价');
            $table->unsignedInteger('refundnum')->default(0)->comment('退货数量');
            $table->unsignedInteger('changingnum')->default(0)->comment('换货数量');

            $table->timestamps();
            $table->index('shop_id');
            $table->index('goods_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('stat_shop_item_statics') . "` comment '商家商品统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_shop_item_statics');
    }
}
