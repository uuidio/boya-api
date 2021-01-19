<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsShowToLotteryRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lottery_records', function (Blueprint $table) {
            $table->tinyInteger('is_show')->nullable()->default(0)->comment('是否展示（0：否，1：是）');
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
