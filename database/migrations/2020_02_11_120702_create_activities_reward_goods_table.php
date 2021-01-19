<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesRewardGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities_reward_goods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('goods_name', 150)->comment('商品名称');
            $table->text('goods_info')->nullable()->comment('商品简介');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->unsignedInteger('goods_id')->default(0)->comment('商品id');
            $table->unsignedInteger('sku_id')->default(0)->comment('商品id');
            $table->unsignedInteger('gc_id')->default(0)->comment('商品分类id');
            $table->unsignedInteger('gc_id_1')->default(0)->comment('一级分类id');
            $table->unsignedInteger('gc_id_2')->default(0)->comment('二级分类id');
            $table->unsignedInteger('gc_id_3')->default(0)->comment('三级分类id');
//            $table->unsignedInteger('activities_id')->default(0)->comment('关联活动id');
//            $table->string('type',60)->nullable()->comment('类型');
            $table->unsignedInteger('brand_id')->default(0)->nullable()->default(0)->comment('品牌id');
            $table->decimal('goods_price', 10, 2)->default(0)->comment('商品价格');
            $table->string('goods_serial', 150)->comment('商品货号');
//            $table->unsignedInteger('goods_stock')->comment('商品库存');
            $table->string('goods_barcode', 50)->nullable()->comment('商品条形码');
            $table->string('spec_name')->nullable()->comment('规格名称');
            $table->string('goods_image')->comment('商品主图');
            $table->text('goods_body')->nullable()->comment('商品描述');
            $table->unsignedTinyInteger('is_use')->default(1)->comment('是否启用 1是，0否');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('goods_id');
            $table->index('sku_id');
            $table->index('gc_id');
            $table->index('brand_id');
//            $table->index('type');
//            $table->index('activities_id');
            $table->index('is_use');
            $table->index('goods_name');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('activities_reward_goods') . "` comment '活动实物奖品池表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities_reward_goods');
    }
}
