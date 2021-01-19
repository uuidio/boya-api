<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lives', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->string('number', 50)->comment('直播间编号');
            $table->string('title', 100)->comment('直播间标题');
            $table->string('subtitle', 100)->comment('直播间副标题');
            $table->string('rollitle', 100)->comment('滚动字幕');
            $table->string('img_url')->comment('直播间封面图');
            $table->text('introduce')->nullable()->comment('直播间简介');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->string('login_account',100)->comment('平台助理登录账号');
            $table->string('password',60)->comment('登录密码');
            $table->string('mobile',32)->nullable()->comment('手机号');
            $table->string('goods_serial', 150)->nullable()->comment('商品货号');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lives');
    }
}
