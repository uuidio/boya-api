<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->comment('标题');
            $table->string('activity_url')->nullable()->default(null)->comment('活动图片');
            $table->unsignedTinyInteger('is_show')->nullable()->default(1)->comment('是否显示，0为否，1为是，默认为1');
            $table->text('content')->comment('活动内容');
            $table->unsignedInteger('listorder')->nullable()->default(0)->comment('列表顺序');
            $table->unsignedTinyInteger('type')->nullable()->default(0)->comment('活动类型');
            $table->unsignedTinyInteger('verify_status')->nullable()->default(0)->comment('审核状态，0为待审核，1为审核通过，2审核不通过');
            $table->string('verify_remark')->nullable()->comment('审核备注');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('member_activities') . "` comment '会员活动表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_activities');
    }
}
