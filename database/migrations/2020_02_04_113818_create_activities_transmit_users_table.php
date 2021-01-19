<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTransmitUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities_transmit_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('transmit_id')->comment('传递活动id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('count')->default(0)->comment('签到次数');
            $table->unsignedInteger('ranking')->default(0)->comment('排名');
            $table->unsignedInteger('level')->default(0)->comment('等级');
            $table->string('wx_qr',255)->nullable()->comment('个人微信分享二维码');
            $table->timestamps();

            $table->index('transmit_id');
            $table->index('user_id');
            $table->index('level');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('activities_transmit_users') . "` comment '传递活动会员参与记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities_transmit_users');
    }
}
