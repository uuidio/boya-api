<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200226TradeOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_orders', function (Blueprint $table) {
            //
            $table->smallInteger('act_reward')->default(0)->comment('推广收益标识,1-预估预估,2-实际收益');
            $table->smallInteger('is_distribution')->default(0)->comment('推广状态,0-否,1-是');

            $table->index('act_reward');
            $table->index('is_distribution');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_orders', function (Blueprint $table) {
            //
        });
    }
}
