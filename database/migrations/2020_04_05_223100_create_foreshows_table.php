<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForeshowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foreshows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->unsignedInteger('live_id')->default(0)->comment('直播间id');
            $table->string('img_url')->comment('直播间封面');
            $table->string('title',100)->comment('直播间标题');
            $table->text('introduce')->nullable()->comment('直播间简介');
            $table->timestamp('start_at')->nullable()->comment('开播时间 ');
            $table->string('goodsids')->comment('商品集合');
            $table->string('wechat')->comment('微信分享');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('foreshows');
    }
}
