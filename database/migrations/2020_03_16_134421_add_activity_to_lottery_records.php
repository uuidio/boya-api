<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddActivityToLotteryRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lottery_records', function (Blueprint $table) {
            $table->bigInteger('activities_id')->nullable()->comment('活动ID');
            $table->string('activities_name')->nullable()->comment('活动名称');
            $table->tinyInteger('activities_type')->nullable()->comment('活动分类');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lottery_records', function (Blueprint $table) {
            //
        });
    }
}
