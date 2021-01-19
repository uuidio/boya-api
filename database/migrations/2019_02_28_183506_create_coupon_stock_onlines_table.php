<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponStockOnlinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_stock_onlines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('coupon_id');
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态(1未使用2已使用3已过期)');
            $table->unsignedTinyInteger('operator')->default(1)->comment('操作者(1用户领取2商家发放)');
            $table->string('coupon_code')->comment('优惠券唯一码');
            $table->timestamps();
            $table->index('coupon_id');
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('coupon_stock_onlines') . "` comment '线上优惠券发行库'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_stock_onlines');
    }
}
