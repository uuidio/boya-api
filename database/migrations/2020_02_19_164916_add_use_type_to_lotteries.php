<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUseTypeToLotteries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lotteries', function (Blueprint $table) {
            $table->tinyInteger('use_type')->nullable()->default(0)->comment('使用类型(0:线上转盘，1：线下多转盘)');
            $table->integer('luck_draw_num')->nullable()->default(1)->comment('每天抽奖次数');
            $table->integer('integral')->nullable()->default(0)->comment('抽奖扣减积分数');
            $table->string('qr_code')->nullable()->comment('二维码');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lotteries', function (Blueprint $table) {
            //
        });
    }
}
