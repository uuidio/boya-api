<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeAftersalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_aftersales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('aftersales_bn', 60)->comment('申请售后编号');
            $table->unsignedInteger('shop_id')->comment('所属商家id');
            $table->unsignedInteger('user_id')->comment('申请会员');
            $table->enum('aftersales_type',['ONLY_REFUND','REFUND_GOODS','EXCHANGING_GOODS'])->default('ONLY_REFUND')->comment('售后服务类型,ONLY_REFUND-仅退款,REFUND_GOODS-退货退款,EXCHANGING_GOODS-换货');
            $table->unsignedInteger('progress')->default(0)->comment('处理进度,0-等待商家处理,1-商家接受申请等待消费者回寄,2-消费者回寄，等待商家收货确认,3-商家已驳回,4-商家已处理,5-商家确认收货,6-平台驳回退款申请,7-平台已处理退款申请,8-同意退款提交到平台等待平台处理,9-会员取消');
            $table->enum('status',['0','1','2','3'])->default(0)->comment('状态,0-待处理,1-处理中,2-已处理,3-已驳回');
            $table->string('tid', 30)->comment('订单号');
            $table->string('oid', 30)->comment('子订单号');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->comment('商品sku_id');
            $table->string('title', 90)->comment('商品标题');
            $table->unsignedInteger('num')->default(0)->comment('申请售后商品数量');
            $table->string('reason')->nullable()->comment('申请售后原因');
            $table->string('description')->nullable()->comment('申请描述');
            $table->string('evidence_pic')->nullable()->comment('图片凭证信息');
            $table->string('shop_explanation')->nullable()->comment('商家处理申请说明');
            $table->string('admin_explanation')->nullable()->comment('平台处理申请说明');
            $table->string('refunds_reason')->nullable()->comment('商家退款备注');
            $table->string('sendback_data')->nullable()->comment('消费者提交退货物流信息');
            $table->string('sendconfirm_data')->nullable()->comment('商家重新发货物流信息');
            $table->string('gift_data')->nullable()->comment('商家重新发货物流信息');
            $table->timestamps();

            $table->unique('aftersales_bn');
            $table->index('user_id');
            $table->index('shop_id');
            $table->index('goods_id');
            $table->index('aftersales_type');
            $table->index('progress');
            $table->index('status');
            $table->index('tid');
            $table->index('oid');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_aftersales') . "` comment '售后申请表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_aftersales');
    }
}
