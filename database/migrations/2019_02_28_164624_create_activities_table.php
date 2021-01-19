<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->comment('活动名称');
            $table->string('desc')->nullable()->comment('活动简介');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->string('channel')->default('all')->comment('使用渠道');
            $table->unsignedTinyInteger('type')->default(0)->comment('活动类型（1满减券2满折3满赠4满X件Y折5限时特价）');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态(0未审核1审核通过2已生效3中止4驳回)');
            $table->string('reason')->nullable()->comment('中止原因');
            $table->string('rule')->comment('活动规则');
            $table->string('user_type')->default('all')->comment('使用者范围');
            $table->unsignedTinyInteger('is_bind_goods')->default(0)->comment('适用范围（0全商品,1部分商品适用,2部分商品不适用）');
            $table->unsignedTinyInteger('is_bind_shop')->default(0)->comment('适用范围（0全商铺,1部分商铺适用,2部分商铺不适用）');
            $table->string('limit_shop')->nullable()->comment('绑定的商铺');
            $table->timestamp('star_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间');
            $table->timestamps();
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('activities') . "` comment '活动信息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities');
    }
}
