<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWxUserinfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wx_userinfos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('openid',50)->comment('微信openid');
            $table->string('nickname',50)->nullable()->comment('用户昵称');
            $table->tinyInteger('sex')->default(0)->comment('性别--1男2女0未知');
            $table->string('province')->nullable()->comment('省份');
            $table->string('city')->nullable()->comment('城市');
            $table->string('country')->nullable()->comment('国家');
            $table->string('headimgurl')->nullable()->comment('用户头像');
            $table->timestamps();
            $table->index('openid');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('wx_userinfos') . "` comment '用户微信信息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wx_userinfos');
    }
}
