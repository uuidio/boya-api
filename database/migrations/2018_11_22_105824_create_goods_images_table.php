<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_images', function (Blueprint $table) {
            $table->increments('id')->comment('商品图片id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('spec_id')->default(0)->comment('规格id');
            $table->string('image_url')->comment('图片地址');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_default')->default(0)->comment('默认主图');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_images') . "` comment '商品图片表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_images');
    }
}
