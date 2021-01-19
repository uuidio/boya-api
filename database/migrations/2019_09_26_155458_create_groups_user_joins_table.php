<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsUserJoinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups_user_joins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment('会员id');
            $table->unsignedBigInteger('open_id')->default(0)->comment('会员open_id');
            $table->text('wechat_head_img')->nullable()->comment('会员头像');
            $table->string('goods_name',100)->comment('商品名称');
            $table->decimal('goods_price', 10, 2)->comment('商品金额');
            $table->decimal('group_price', 10, 2)->comment('拼团金额');
            $table->unsignedBigInteger('groups_id')->comment('拼团促销id');
            $table->string('groups_bn',100)->comment('拼团促销订单编码');
            $table->unsignedBigInteger('goods_id')->comment('商品id');
            $table->unsignedBigInteger('sku_id')->comment('sku_id');
            $table->string('payment_id',100)->comment('关联支付单号');
            $table->string('tid',100)->comment('关联订单号');
            $table->unsignedBigInteger('status')->default(0)->comment('订单状态,0-待付款,1-已付款,2-申请退款');
            $table->string('refund_bn',60)->nullable()->comment('关联退款单号');
            $table->unsignedBigInteger('is_header')->default(0)->comment('是否团长,0-不是,1-是');
            $table->unsignedBigInteger('notification_type')->default(0)->comment('通知:0=未通知|1=已通知待完成');

            $table->index('user_id');
            $table->index('groups_id');
            $table->index('groups_bn');
            $table->index('goods_id');
            $table->index('sku_id');
            $table->index('status');

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
        Schema::dropIfExists('groups_user_joins');
    }
}
