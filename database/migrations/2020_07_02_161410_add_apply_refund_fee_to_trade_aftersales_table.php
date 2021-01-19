<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApplyRefundFeeToTradeAftersalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_aftersales', function (Blueprint $table) {
            $table->decimal('apply_refund_price', 10, 2)->default(0)->comment('申请退款金额');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_aftersales', function (Blueprint $table) {
            //
        });
    }
}
