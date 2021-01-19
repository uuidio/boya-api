<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGradeLimitToLotteriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lotteries', function (Blueprint $table) {
            //
            $table->tinyInteger('is_grade_limit')->nullable()->default(0)->comment('是否开启会员等级限制抽奖（0：否，1：是）');
            $table->string('grade_limit')->nullable()->comment('等级限制');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lotteries', function (Blueprint $table) {
            //
        });
    }
}
