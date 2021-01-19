<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLotteryRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lottery_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_account_id')->nullable()->comment('会员ID');
            $table->bigInteger('lottery_id')->nullable()->comment('奖项ID');
            $table->string('lottery_name')->nullable()->comment('奖项名称');
            $table->integer('number')->nullable()->default(1)->comment('中奖数量');
            $table->tinyInteger('status')->nullable()->default(0)->comment('中奖状态（0：未中奖,1：中奖）');
            $table->tinyInteger('grant_status')->nullable()->default(0)->comment('奖品发放状态（0：未发放,1：已发放）');
            $table->dateTime('grant_time')->nullable()->comment('奖品发放时间');
            $table->date('luck_draw_date')->nullable()->comment('抽奖日期');
            $table->string('prize')->nullable()->comment('奖品信息');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('lottery_records') . "` comment '抽奖记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lottery_records');
    }
}
