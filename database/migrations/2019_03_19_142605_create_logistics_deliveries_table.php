<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogisticsDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logistics_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('delivery_id', 30)->comment('退款单号');
            $table->string('tid', 30)->comment('订单号');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('seller_id')->nullable()->comment('商家账号');
            $table->unsignedInteger('shop_id')->nullable()->comment('店铺ID');
            $table->decimal('post_fee', 10, 2)->default(0)->comment('物流费用');
            $table->boolean('is_protect')->default(0)->comment('是否保价');
            $table->unsignedInteger('dlytmpl_id')->default(0)->comment('配送方式');
            $table->unsignedInteger('logi_id')->default(0)->comment('物流公司ID');
            $table->string('logi_name',100)->nullable()->comment('物流公司名称');
            $table->string('corp_code',100)->nullable()->comment('物流公司代码');
            $table->string('logi_no',50)->nullable()->comment('物流单号');
            $table->string('receiver_name',50)->nullable()->comment('收货人姓名');
            $table->string('receiver_province',20)->nullable()->comment('收货人所在省');
            $table->string('receiver_city',20)->nullable()->comment('收货人所在市');
            $table->string('receiver_district',20)->nullable()->comment('收货人所在地区');
            $table->string('receiver_address',200)->nullable()->comment('收货人详细地址');
            $table->string('receiver_zip',20)->nullable()->comment('收货人邮编');
            $table->string('receiver_mobile',30)->nullable()->comment('收货人手机号');
            $table->string('receiver_phone',30)->nullable()->comment('收货人电话');
            $table->timestamp('t_send')->nullable()->comment('单据结束时间');
            $table->timestamp('t_confirm')->nullable()->comment('单据确认时间');
            $table->string('op_name',30)->nullable()->comment('操作员');
            $table->enum('status',['succ','failed','cancel','lost','progress','timeout','ready'])->default('ready')->comment('状态,succ-成功到达,failed-发货失败,cancel-已取消,lost-货物丢失,progress-运送中,timeout-超时,ready-准备发货');
            $table->text('memo')->nullable()->comment('备注');
            $table->boolean('disabled')->default(0)->comment('无效与否');
            $table->timestamps();

            $table->unique('delivery_id');
            $table->index('tid');
            $table->index('logi_no');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('logistics_deliveries') . "` comment '发货单表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logistics_deliveries');
    }
}