<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddActivityNumPointActivityGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('point_activity_goods', function (Blueprint $table) {
            $table->unsignedInteger('activity_buy_max')->default(999999)->comment('活动时间内每人-购买限制');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('point_activity_goods', function (Blueprint $table) {
            //
        });
    }
}
