<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountFieldToTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            //
            $table->decimal('platform_discount', 10, 2)->default(0)->comment('平台优惠卷金额');
            $table->decimal('seller_coupon_discount', 10, 2)->default(0)->comment('商家优惠卷金额');
            $table->decimal('seller_discount', 10, 2)->default(0)->comment('店铺促销金额');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trades', function (Blueprint $table) {
            //
        });
    }
}
