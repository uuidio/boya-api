<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesRewardsSendLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities_rewards_send_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('activities_id')->comment('关联活动id');
            $table->unsignedInteger('activities_reward_id')->comment('关联兑换商品id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('tid',60)->nullable()->comment('关联订单号');
            $table->string('type',60)->nullable()->comment('类型');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->unsignedInteger('is_redeem')->default(0)->comment('是否兑换0-否,1-已兑换,待发货,2-已发货');
            $table->timestamps();

            $table->index('activities_id');
            $table->index('activities_reward_id');
            $table->index('user_id');
            $table->index('tid');
            $table->index('type');
            $table->index('is_redeem');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('activities_rewards_send_logs') . "` comment '活动领奖记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities_rewards_send_logs');
    }
}
