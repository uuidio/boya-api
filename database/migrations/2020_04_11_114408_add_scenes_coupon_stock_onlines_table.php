<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScenesCouponStockOnlinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupon_stock_onlines', function (Blueprint $table) {
            $table->unsignedTinyInteger('scenes')->default(1)->comment('使用场景（1线上2线下3全部）');
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
