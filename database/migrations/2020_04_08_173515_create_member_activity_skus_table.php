<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberActivitySkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('member_activity_skus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('place')->comment('活动场地');
            $table->bigInteger('activity_id')->default(0)->comment('活动id');
            $table->decimal('money', 10, 2)->default(0)->comment('报名费用');
            $table->bigInteger('point')->default(0)->comment('报名积分');
            $table->string('voucher')->nullable()->default(0)->comment('报名凭证');
            $table->bigInteger('max_people_num')->default(0)->comment('报名最大人数，0为不限制');
            $table->bigInteger('min_people_num')->default(0)->comment('报名最少人数');
            $table->unsignedTinyInteger('apply_way')->nullable()->default(1)->comment('报名方式：1：免费 2、金额 3、积分/牛币 4、积分+金额 5、凭证');
            $table->timestamp('apply_start_time')->nullable()->comment('报名开始时间');
            $table->timestamp('apply_end_time')->nullable()->comment('报名结束时间');
            $table->timestamp('activity_start_time')->nullable()->comment('活动开始时间');
            $table->timestamp('activity_end_time')->nullable()->comment('活动结束时间');
            $table->unsignedTinyInteger('verify_status')->nullable()->default(0)->comment('审核状态，0为待审核，1为审核通过，2审核不通过');
            $table->string('verify_remark')->nullable()->comment('审核备注');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('member_activity_skus') . "` comment '会员活动场次表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_activity_skus');
    }
}
