<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeEstimatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_estimates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('pid')->default(0)->comment('推广员id');
            $table->string('tid',60)->comment('关联订单号');
            $table->string('oid',60)->comment('关联子订单号');
            $table->decimal('reward_value', 10, 2)->default(0)->comment('金额');
            $table->smallInteger('type')->comment('类型,0-平台分销,1-商家推物');
            $table->text('remark')->nullable()->comment('备注');
            $table->smallInteger('iord')->default(1)->comment('1-增加,2-减少');
            $table->smallInteger('status')->default(0)->comment('订单状态,0-订单正常,1-取消退款,2-售后退款');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('goods_id');
            $table->index('user_id');
            $table->index('pid');
            $table->index('tid');
            $table->index('oid');
            $table->index('type');
            $table->index('iord');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_estimates') . "` comment '预估收益表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_estimates');
    }
}
