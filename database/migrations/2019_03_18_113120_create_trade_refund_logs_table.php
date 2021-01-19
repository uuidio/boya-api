<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeRefundLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_refund_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('money', 10, 2)->default(0)->comment('退款金额');
            $table->decimal('cur_money', 10, 2)->default(0)->comment('退款金额');
            $table->string('refund_bank', 50)->nullable()->comment('退款银行');
            $table->string('refund_account', 50)->nullable()->comment('退款账号');
            $table->string('refund_people', 50)->nullable()->comment('退款人');
            $table->string('receive_bank', 50)->nullable()->comment('收款银行');
            $table->string('receive_account', 50)->nullable()->comment('收款账号');
            $table->string('beneficiary', 50)->nullable()->comment('收款人');
            $table->string('currency', 50)->nullable()->comment('货币');
            $table->decimal('paycost', 10, 2)->default(0)->comment('支付网关费用');
            $table->enum('status',['ready','progress','succ','failed','cancel'])->default('ready')->comment('支付状态,ready-准备中,progress-处理中,succ-支付成功,failed-支付失败,cancel-取消');
            $table->unsignedInteger('op_id')->comment('操作员');
            $table->enum('type',['online','offline','deposit'])->default('online')->comment('退款方式,online-在线退款,offline-线下退款,deposit-预存款退款');
            $table->enum('refunds_type',['0','1','2'])->default('0')->comment('退款类型,0-售后申请退款,1-取消订单退款,2-拒收订单退款');
            $table->string('refund_bn', 60)->nullable()->comment('退款单号');
            $table->string('pay_app', 60)->comment('支付方式');
            $table->timestamp('finish_time')->nullable()->comment('支付完成时间');
            $table->string('memo', 60)->nullable()->comment('备注');
            $table->string('tid', 30)->comment('订单号');
            $table->string('oid', 30)->default(0)->comment('子订单号');
            $table->string('refunds_id', 30)->default(0)->comment('refunds表主键');
            $table->string('aftersales_bn', 30)->default(0)->comment('售后单号');
            $table->decimal('return_fee', 10, 2)->comment('商家退款金额');

            $table->timestamps();

            $table->index('refunds_id');
            $table->index('refund_bn');
            $table->index('tid');
            $table->index('oid');
        });


        DB::statement("ALTER TABLE `" . prefixTableName('trade_refund_logs') . "` comment '订单追评表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_refund_logs');
    }
}
