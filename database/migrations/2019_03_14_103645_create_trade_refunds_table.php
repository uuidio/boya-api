<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_refunds', function (Blueprint $table) {
            $table->increments('id');
            $table->string('refund_bn', 60)->comment('退款申请编号');
            $table->unsignedInteger('shop_id')->comment('所属商家id');
            $table->unsignedInteger('user_id')->comment('申请会员');
            $table->string('tid', 30)->comment('订单号');
            $table->string('oid', 30)->default(0)->comment('子订单号');
            $table->string('aftersales_bn', 30)->default(0)->comment('申请售后编号');
            $table->enum('refunds_type',['0','1','2'])->default(0)->comment('退款类型,0-售后申请退款,1-取消订单退款,2-拒收订单退款');
            $table->enum('status',['0','1','2','3','4','5','6'])->default(0)->comment('审核状态,0-未审核,1-已完成退款,2-已驳回,3-商家审核通过,4-商家审核不通过,5-商家强制关单,6-平台强制关单');
            $table->string('refunds_reason')->nullable()->comment('申请退款原因');
            $table->decimal('order_price', 10, 2)->default(0)->comment('订单金额');
            $table->decimal('total_price', 10, 2)->default(0)->comment('应退金额');
            $table->decimal('refund_fee', 10, 2)->default(0)->comment('实退金额');
            $table->decimal('points_fee', 10, 2)->default(0)->comment('积分抵扣金额');
            $table->decimal('coupon_fee', 10, 2)->default(0)->comment('优惠支付金额');
            $table->string('user_coupon_id',30)->default(0)->comment('使用优惠的集合');
            $table->enum('return_freight',['1','2'])->default(1)->comment('是否退运费,1-退运费,2-不退运费');
            $table->bigInteger('consume_point_fee')->default(0)->comment('抵扣的积分');
            $table->bigInteger('refund_point')->default(0)->comment('实退积分');
            $table->timestamps();

            $table->unique('refund_bn');
            $table->index('user_id');
            $table->index('shop_id');
            $table->index('refunds_type');
            $table->index('status');
            $table->index('tid');
            $table->index('oid');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_refunds') . "` comment '退款申请表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_refunds');
    }
}
