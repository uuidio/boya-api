<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatTradeChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_trade_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('trade_at')->nullable()->comment('交易时间');
            $table->timestamp('refund_at')->nullable()->comment('退款成功时间');
            $table->timestamp('finish_at')->nullable()->comment('订单完成时间');

            $table->string('tid', 30)->nullable()->comment('订单号');
            $table->string('trade_type', 30)->nullable()->comment('交易类型');
            $table->string('refund_bn', 30)->nullable()->comment('退款单号');
            $table->string('payment_id', 30)->comment('商户订单号（支付单号)');
            $table->decimal('payed_fee', 10, 2)->default(0)->comment('支付金额');
            $table->decimal('refund_fee', 10, 2)->default(0)->comment('退款金额');


            $table->string('status', 50)->default('0')->comment('对账状态');
            $table->string('deal_status', 50)->default('0')->comment('处理状态');
            $table->string('import_status', 50)->default('1')->comment('导入状态');
            $table->text('remark')->nullable()->comment('备注');
            $table->string('handler')->default('PLATFORM')->comment('处理人');
            $table->string('abnormal_reason')->nullable()->comment('异常原因');
            $table->unsignedInteger('gm_id')->nullable()->comment('集团id');
            $table->unsignedInteger('shop_id')->nullable()->comment('店铺ID');

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
        Schema::dropIfExists('wechat_trade_checks');
    }
}
