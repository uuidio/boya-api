<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTidInCoupononlineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupon_stock_onlines', function (Blueprint $table) {
            $table->string('tid', 30)->nullable()->comment('订单号');
            $table->index('tid');
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
