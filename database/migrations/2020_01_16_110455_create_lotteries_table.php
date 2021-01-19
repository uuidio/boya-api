<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLotteriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lotteries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable()->comment('奖项名称');
            $table->tinyInteger('type')->nullable()->default(0)->comment('奖项分类(0:谢谢惠顾，1：积分，2：电影票)');
            $table->string('prize')->nullable()->comment('奖品');
            $table->integer('number')->nullable()->default(0)->comment('奖品数量');
            $table->integer('remnant_num')->nullable()->default(0)->comment('剩余奖品数量');
            $table->string('probability')->nullable()->default(0)->comment('中奖概率');
            $table->tinyInteger('status')->nullable()->default(1)->comment('启用状态');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('lotteries') . "` comment '奖项表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lotteries');
    }
}
