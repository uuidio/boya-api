<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxPointActivityGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('point_activity_goods', function (Blueprint $table) {
            $table->unsignedInteger('buy_max')->default(999999)->comment('每人-购买限制');
            $table->unsignedInteger('day_buy_max')->default(999999)->comment('每天最大购买限制');
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
