<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentIdToCouponStockOnlines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupon_stock_onlines', function (Blueprint $table) {
            $table->string('payment_id', 30)->nullable()->comment('支付单号');
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupon_stock_onlines', function (Blueprint $table) {
            //
        });
    }
}
