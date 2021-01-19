<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpActivitiesToActivitiesRewardsSendLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities_rewards_send_logs', function (Blueprint $table) {
            $table->renameColumn('lottery_name', 'activities_name');
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
