<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAfterStateToTradeAftersalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_aftersales', function (Blueprint $table) {
            //
            $table->unsignedTinyInteger('after_state')->default(0)->comment('申请售后之后的状态');
            $table->string('goods_barcode',100)->nullable()->comment('商品条形码');
            $table->string('return_order_sn', 60)->nullable()->comment('erp里的退单编号');
            $table->string('type', 30)->nullable()->comment('订单类型');
            $table->unsignedTinyInteger('erp_push')->default(0)->comment('erp推送状态,0未推送,1已推送,2推送失败');
            $table->text('erp_push_message')->nullable()->comment('erp_push异常原因');
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
