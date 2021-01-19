<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatLivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_lives', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('roomid')->comment('房间id');
            $table->string('name')->nullable()->comment('房间名');
            $table->string('anchor_name')->nullable()->comment('主播名');
            $table->string('cover_img')->nullable()->comment('封面图片url');
            $table->timestamp('start_time')->nullable()->comment('直播计划开始时间');
            $table->timestamp('end_time')->nullable()->comment('直播计划结束时间');
            $table->unsignedInteger('room_status')->default(0)->comment('房间开启状态');
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
        Schema::dropIfExists('wechat_lives');
    }
}
