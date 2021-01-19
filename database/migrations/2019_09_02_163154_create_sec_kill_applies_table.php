<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSecKillAppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sec_kill_applies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('activity_name', 100)->nullable()->comment('活动名称');
            $table->string('activity_tag', 100)->nullable()->comment('活动标签');
            $table->string('activity_desc', 255)->nullable()->comment('活动描述');
            $table->timestamp('apply_begin_time')->nullable()->comment('申请活动开始时间');
            $table->timestamp('apply_end_time')->nullable()->comment('申请活动结束时间');
            $table->timestamp('release_time')->nullable()->comment('发布时间');
            $table->timestamp('start_time')->nullable()->comment('活动生效开始时间');
            $table->timestamp('end_time')->nullable()->comment('活动生效结束时间');
            $table->unsignedInteger('enroll_limit')->default(0)->comment('店铺报名限制数量');
            $table->unsignedInteger('limit_cat')->default(0)->comment('报名类目限制');
            $table->unsignedInteger('shoptype')->default(0)->comment('店铺类型限制');
            $table->unsignedInteger('enabled')->default(1)->comment('是否启用1启用,0不启用');
            $table->string('remind_way', 30)->nullable()->comment('提醒方式');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('sec_kill_applies') . "` comment '秒杀活动规则表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sec_kill_applies');
    }
}
