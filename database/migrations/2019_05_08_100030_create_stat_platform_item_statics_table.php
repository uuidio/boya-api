<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatPlatformItemStaticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_platform_item_statics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->string('shop_name',100)->default(0)->comment('所属商家');
            $table->unsignedInteger('cat_id')->default(0)->comment('一级分类id');
            $table->string('cat_name',100)->nullable()->comment('所属一级分类名称');
            $table->unsignedInteger('goods_id')->default(0)->comment('商品id');
            $table->string('title',100)->nullable()->comment('商品标题');
            $table->string('pic_path',200)->nullable()->comment('商品图片');
            $table->string('itemurl',200)->nullable()->comment('商品连接');
            $table->unsignedInteger('amountnum')->default(0)->comment('销售数量');
            $table->decimal('amountprice', 10, 2)->default(0)->comment('销售总价');

            $table->timestamps();
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('stat_platform_item_statics') . "` comment '平台运营商商品统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_platform_item_statics');
    }
}
