<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeAftersaleLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_aftersale_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tid', 30)->comment('订单号');
            $table->string('oid', 30)->comment('子订单号');
            $table->tinyInteger('aftersales_type')->comment('售后服务类型:0仅退款,1退货退款,2换货');
            $table->unsignedInteger('progress')->comment('处理进度,0-等待商家处理,1-商家接受申请等待消费者回寄,2-消费者回寄，等待商家收货确认,3-商家已驳回,4-商家已处理,5-商家确认收货,6-平台驳回退款申请,7-平台已处理退款申请,8-同意退款提交到平台等待平台处理,9-会员取消');
            $table->tinyInteger('status')->nullable()->comment('状态,0-待处理,1-处理中,2-已处理,3-已驳回');
            $table->string('mes')->nullable()->comment('操作备注');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_aftersale_logs');
    }
}
