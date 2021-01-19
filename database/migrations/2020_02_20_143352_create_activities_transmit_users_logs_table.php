<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTransmitUsersLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities_transmit_users_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('transmit_id')->comment('传递活动id');
            $table->timestamps();

            $table->index('transmit_id');
            $table->index('user_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('activities_transmit_users_logs') . "` comment '传递活动会员参与记录明细'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities_transmit_users_logs');
    }
}
