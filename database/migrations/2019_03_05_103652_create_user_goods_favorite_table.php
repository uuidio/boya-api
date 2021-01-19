<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGoodsFavoriteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_goods_favorite', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('gc_id')->comment('商品分类id');
            $table->string('goods_name', 150)->comment('商品名称');
            $table->decimal('goods_price', 10, 2)->comment('商品价格');
            $table->string('goods_image')->comment('商品主图');
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_goods_favorite') . "` comment '会员收藏商品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_goods_favorite');
    }
}
