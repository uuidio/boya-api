<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tid', 30)->comment('订单号');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('dlytmpl_ids', 50)->nullable()->comment('订单状态');
            $table->string('ziti_addr')->nullable()->comment('自提地址');
            $table->string('status', 50)->default('WAIT_BUYER_PAY')->comment('订单状态');
            $table->string('cancel_status', 50)->default('NO_APPLY_CANCEL')->comment('取消订单状态');
            $table->text('cancel_reason')->nullable()->comment('取消原因');
            $table->string('pay_type', 50)->default('online')->comment('支付类型');
            $table->decimal('amount', 10, 2)->default(0)->comment('实付金额');

            $table->decimal('points_fee', 10, 2)->default(0)->comment('积分抵扣金额');
            $table->decimal('total_fee', 10, 2)->default(0)->comment('商品总金额');
            $table->decimal('post_fee', 10, 2)->default(0)->comment('邮费');
            $table->decimal('discount_fee', 10, 2)->default(0)->comment('促销优惠金额');
            $table->unsignedInteger('obtain_point_fee')->default(0)->comment('买家获得积分');
            $table->unsignedInteger('consume_point_fee')->default(0)->comment('买家消耗积分');

            $table->string('receiver_name', 50)->comment('收货人姓名');
            $table->string('receiver_province', 50)->comment('收货人所在省份');
            $table->string('receiver_city', 50)->comment('收货人所在城市');
            $table->string('receiver_county', 50)->comment('收货人所在地区');
            $table->string('receiver_address', 200)->comment('收货人详细地址');
            $table->string('receiver_zip', 50)->comment('收货人邮编');
            $table->string('receiver_tel', 50)->comment('收货人电话');
            $table->string('receiver_housing_name', 50)->nullable()->comment('收货人所在小区名称');
            $table->unsignedTinyInteger('receiver_housing_id')->nullable()->comment('收货人所在小区id');

            $table->text('trade_memo')->nullable()->comment('交易备注');
            $table->unsignedTinyInteger('buyer_rate')->default(0)->comment('买家是否已评价');
            $table->text('buyer_message')->nullable()->comment('买家留言');
            $table->unsignedTinyInteger('disabled')->default(1)->comment('是否有效');
            $table->text('shop_memo')->nullable()->comment('卖家备注');
            $table->string('shop_flag')->nullable()->comment('卖家备注旗帜');
            $table->unsignedTinyInteger('is_clearing')->default(0)->comment('是否生成结算单');

            $table->unsignedTinyInteger('need_invoice')->default(0)->comment('是否需要开票');
            $table->string('invoice_name', 100)->nullable()->comment('发票抬头');
            $table->string('invoice_type', 100)->nullable()->comment('发票类型');
            $table->string('invoice_main', 100)->nullable()->comment('发票内容');
            $table->string('invoice_tax_code', 100)->nullable()->comment('纳税识别号');
            $table->text('invoice_vat_main')->nullable()->comment('增值税发票内容');

            $table->string('shipping_type', 30)->nullable()->comment('运送方式');
            $table->string('invoice_no', 30)->nullable()->comment('运单号');

            $table->string('ip')->nullable()->comment('IP地址');
            $table->string('need_arrive_time')->nullable()->comment('买家要求送达时间');
            $table->timestamp('pay_time')->nullable()->comment('付款时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间,整个订单收货or售后完结的时间');
            $table->timestamp('consign_time')->nullable()->comment('卖家发货时间');
            $table->timestamp('arrive_time')->nullable()->comment('送达时间');
            $table->string('ziti_memo')->nullable()->comment('自提备注');
            $table->timestamps();

            $table->unique('tid');
            $table->index('status');
            $table->index('user_id');
            $table->index('shop_id');
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trades') . "` comment '订单主表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trades');
    }
}
