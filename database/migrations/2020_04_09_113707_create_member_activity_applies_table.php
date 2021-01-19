<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberActivityAppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_activity_applies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->default(0)->comment('用户id');
            $table->bigInteger('activity_id')->default(0)->comment('活动id');
            $table->bigInteger('activity_sku_id')->default(0)->comment('活动场次id');
            $table->string('voucher')->nullable()->default(0)->comment('报名凭证');
            $table->unsignedTinyInteger('verify_status')->nullable()->default(1)->comment('报名状态：1、待审核 2、报名通过 3、报名失败');
            $table->string('verify_remark')->nullable()->comment('审核备注');
            $table->timestamps();

            $table->index('user_id');            
            $table->index('activity_id');
            $table->index('activity_sku_id');

        });
        DB::statement("ALTER TABLE `" . prefixTableName('member_activity_applies') . "` comment '会员活动场次报名表'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_activity_applies');
    }
}
