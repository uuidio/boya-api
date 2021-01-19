<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeCancelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_cancels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cancel_id', 60)->comment('取消订单表id');
            $table->unsignedInteger('shop_id')->comment('所属商家id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('tid', 30)->comment('订单号');
            $table->enum('pay_type',['online','offline'])->default('online')->comment('支付类型,online-线上付款,offline-货到付款');
            $table->decimal('refund_fee', 10, 2)->default(0)->comment('实退金额');
            $table->string('reason')->nullable()->comment('取消原因');
            $table->string('shop_reject_reason')->nullable()->comment('商家拒绝理由');
            $table->enum('cancel_from',['buyer','shop','admin'])->default('buyer')->comment('类型,buyer-用户取消订单,shop-商家取消订单,shopadmin-平台取消订单');
            $table->enum('process',['0','1','2','3'])->default(0)->comment('处理进度,0-提交申请,1-取消处理,2-退款处理,3-完成');
            $table->enum('refunds_status',['WAIT_CHECK','WAIT_REFUND','SHOP_CHECK_FAILS','FAILS','SUCCESS'])->default('WAIT_CHECK')->comment('退款状态,WAIT_CHECK-等待审核,WAIT_REFUND-等待退款,SHOP_CHECK_FAILS-商家审核不通过,FAILS-退款失败,SUCCESS-退款成功');
            $table->timestamps();

            $table->unique('cancel_id');
            $table->index('tid');
            $table->index('shop_id');
            $table->index('process');
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_cancels') . "` comment '取消订单表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_cancels');
    }
}
