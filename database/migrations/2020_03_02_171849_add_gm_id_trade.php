<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGmIdTrade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_activity_details', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_aftersales', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_cancels', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_day_settle_account_details', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_day_settle_accounts', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_month_settle_accounts', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_orders', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_refunds', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trade_splits', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('trades', function (Blueprint $table) {
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
