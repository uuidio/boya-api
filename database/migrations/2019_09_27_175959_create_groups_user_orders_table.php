<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsUserOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups_user_orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->comment('会员id');
            $table->string('groups_bn',100)->comment('拼团促销订单编码,拼团活动id加上开图支付单payment_id');
            $table->unsignedBigInteger('groups_id')->comment('拼团促销id');
            $table->string('tid',60)->nullable()->comment('订单id');
            $table->string('goods_name',150)->comment('商品名称');
            $table->unsignedBigInteger('goods_id')->comment('商品id');
            $table->unsignedBigInteger('sku_id')->comment('sku_id');
            $table->unsignedBigInteger('group_number')->comment('拼团人数');
            $table->unsignedBigInteger('status')->default(1)->comment('发起拼团状态,0-失败,1-进行中,2-成功,3-申请退款');
            $table->timestamp('start_time')->nullable()->comment('开始有效时间');
            $table->timestamp('end_time')->nullable()->comment('结束有效时间');

            $table->index('user_id');
            $table->index('groups_id');
            $table->index('tid');
            $table->index('goods_id');
            $table->index('sku_id');
            $table->index('groups_bn');
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
        Schema::dropIfExists('groups_user_orders');
    }
}
