<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatPlatformTradeStaticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_platform_trade_statics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('new_trade')->default(0)->comment('新增订单数');
            $table->decimal('new_fee', 10, 2)->default(0)->comment('新增订单额');
            $table->enum('stats_trade_from',['all','pc','wap','app'])->default('wap')->comment('来源,all-所有,pc-电脑端,wap-手机触屏,app-手机APP');
            $table->unsignedInteger('complete_trade')->default(0)->comment('已完成订单数');
            $table->decimal('complete_fee', 10, 2)->default(0)->comment('已完成订单额');
            $table->unsignedInteger('refund_trade')->default(0)->comment('已退款的订单数');
            $table->unsignedInteger('refunds_num')->default(0)->comment('已退款的订单数量');
            $table->decimal('refunds_fee', 10, 2)->default(0)->comment('已退款的订单额');

            $table->timestamps();
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('stat_platform_trade_statics') . "` comment '平台运营商交易统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_platform_trade_statics');
    }
}
