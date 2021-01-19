<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsCountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_count', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('goods_id')->comment('商品ID');
            $table->unsignedInteger('sold_quantity')->default(0)->comment('商品销量');
            $table->unsignedInteger('rate_count')->default(0)->comment('评论次数');
            $table->unsignedInteger('rate_good_count')->default(0)->comment('好评次数');
            $table->unsignedInteger('rate_neutral_count')->default(0)->comment('中评次数');
            $table->unsignedInteger('rate_bad_count')->default(0)->comment('差评次数');
            $table->unsignedInteger('view_count')->default(0)->comment('浏览次数');
            $table->unsignedInteger('buy_count')->default(0)->comment('购买次数');
            $table->unsignedInteger('aftersales_month_count')->default(0)->comment('最近一个月售后次数');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_count') . "` comment '商品相关统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_count');
    }
}
