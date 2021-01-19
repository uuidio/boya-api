<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecialActivityItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_activity_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('act_id')->comment('活动id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->string('goods_name')->comment('商品名称');
            $table->decimal('goods_price', 10, 2)->default(0)->nullable()->comment('商品价格');
            $table->string('goods_image')->nullable()->comment('商品图片');
            $table->decimal('act_price', 10, 2)->default(0)->nullable()->comment('活动价格');
            $table->decimal('reduce_price', 10, 2)->default(0)->nullable()->comment('优惠金额');
            $table->string('discount',10)->nullable()->comment('折扣');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态(0未审核1审核通过2审核不通过)');
            $table->timestamps();
            $table->index('shop_id');
            $table->index('act_id');
            $table->index('goods_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('special_activity_items') . "` comment '专题活动商品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('special_activity_items');
    }
}
