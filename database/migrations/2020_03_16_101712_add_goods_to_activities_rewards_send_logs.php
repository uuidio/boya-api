<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGoodsToActivitiesRewardsSendLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities_rewards_send_logs', function (Blueprint $table) {
            $table->string('lottery_name')->nullable()->comment('活动名称');
            $table->string('goods_name')->nullable()->comment('商品名称');
            $table->string('goods_image')->nullable()->comment('商品图片');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activities_rewards_send_logs', function (Blueprint $table) {
            //
        });
    }
}
