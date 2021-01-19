<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bn')->comment('优惠券唯一码');
            $table->unsignedInteger('coupon_id')->comment('优惠券id');;
            $table->unsignedTinyInteger('status')->default(1)->comment('状态(1未使用2已使用3已过期)');
            $table->unsignedTinyInteger('print')->default(0)->comment('打印次数');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('coupon_stocks') . "` comment '线下优惠券发行库'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_stocks');
    }
}
