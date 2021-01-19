<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDenominationsToCouponStockOnlines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupon_stock_onlines', function (Blueprint $table) {
            $table->decimal('coupon_fee', 10, 2)->default(0)->nullable()->comment('面值');
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
