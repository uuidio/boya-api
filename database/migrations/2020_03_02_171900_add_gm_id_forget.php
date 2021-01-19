<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGmIdForget extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_stock_logs', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('rate_appeal', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_log', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('sec_kill_stock_logs', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('coupon_stock_onlines', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('stat_platform_trade_statics', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_refund_logs', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_paybills', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
