<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('oid', 30)->comment('子订单编号');
            $table->string('tid', 30)->comment('订单号');

            $table->unsignedInteger('gc_id')->comment('类目id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('user_id')->comment('会员id');

            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->comment('货品id');
            $table->string('goods_serial', 150)->nullable()->comment('商品货号');
            $table->string('goods_name')->nullable()->comment('商品名称');
            $table->string('goods_image')->nullable()->comment('商品主图');
            $table->decimal('goods_price', 10, 2)->default(0)->comment('商品价格');
            $table->unsignedInteger('quantity')->comment('购买数量');
            $table->unsignedInteger('sendnum')->default(0)->comment('明细商品发货数量');
            $table->string('sku_info')->nullable()->comment('sku描述');
            $table->string('sku_value')->nullable()->comment('SKU的值');
            $table->unsignedTinyInteger('is_oversold')->default(0)->comment('是否超卖');

            $table->decimal('amount', 10, 2)->default(0)->comment('实付金额');
            $table->decimal('total_fee', 10, 2)->default(0)->comment('应付金额');
            $table->decimal('avg_discount_fee', 10, 2)->default(0)->comment('优惠分摊');
            $table->decimal('avg_points_fee', 10, 2)->default(0)->comment('积分抵扣的金额');

            $table->string('status', 50)->default('WAIT_BUYER_PAY')->comment('子订单状态');
            $table->string('after_sales_status', 50)->nullable()->comment('售后状态');
            $table->string('complaint_status', 50)->nullable()->comment('订单投诉状态');

            $table->string('refund_id', 30)->nullable()->comment('订单号');
            $table->decimal('refund_fee', 10, 2)->default(0)->comment('退款金额');

            $table->string('shipping_type', 30)->nullable()->comment('运送方式');
            $table->string('invoice_no', 30)->nullable()->comment('运单号');
            $table->unsignedTinyInteger('buyer_rate')->default(0)->comment('买家是否已评价');
            $table->unsignedTinyInteger('seller_rate')->default(0)->comment('卖家是否已评价');

            $table->timestamp('pay_time')->nullable()->comment('付款时间');
            $table->timestamp('consign_time')->nullable()->comment('发货时间');
            $table->timestamp('arrive_time')->nullable()->comment('送达时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间,整个订单收货or售后完结的时间');
            $table->timestamps();

            $table->unique('oid');
            $table->index('tid');
            $table->index('user_id');
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_orders') . "` comment '订单子表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_orders');
    }
}
