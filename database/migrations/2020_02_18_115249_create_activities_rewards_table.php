<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities_rewards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('activities_reward_goods_id')->comment('关联商品id');
            $table->unsignedInteger('activities_id')->default(0)->comment('关联活动id');
            $table->string('type', 60)->nullable()->comment('类型');
            $table->unsignedInteger('goods_stock')->comment('商品库存');
            $table->unsignedTinyInteger('is_use')->default(1)->comment('是否启用 1是，0否');
            $table->timestamps();

            $table->index('activities_reward_goods_id');
            $table->index('activities_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('activities_rewards') . "` comment '活动关联的实物奖品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities_rewards');
    }
}
