<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDealAtToWechatTradeChecks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wechat_trade_checks', function (Blueprint $table) {
            $table->timestamp('deal_at')->nullable()->comment('返款时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wechat_trade_checks', function (Blueprint $table) {
            //
        });
    }
}
