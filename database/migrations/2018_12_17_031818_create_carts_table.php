<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->default(0)->comment('sku的id');
            $table->string('goods_name', 150)->comment('商品名称');
            $table->text('goods_info')->nullable()->comment('商品简介');
            $table->string('goods_image')->comment('商品默认图');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->unsignedTinyInteger('is_checked')->default(0)->comment('是否购物车选中');
            $table->longText('params')->nullable()->comment('附加参数');

            $table->timestamps();

            $table->index('user_id');
            $table->index('shop_id');
            $table->index('goods_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('carts') . "` comment '购物车'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carts');
    }
}
