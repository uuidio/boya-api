<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogisticsDeliveryDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logistics_delivery_details', function (Blueprint $table) {
            $table->increments('id');
            $table->string('delivery_id',30)->comment('发货单号');
            $table->string('oid', 30)->nullable()->comment('发货明细子订单号');
            $table->enum('item_type',['item','gift','pkg','adjunct'])->default('item')->comment('商品类型,item-商品,gift-赠品,pkg-捆绑商品,adjunct-配件商品');
            $table->unsignedInteger('sku_id')->default(0)->comment('发货明细子订单号');
            $table->string('sku_bn',30)->nullable()->comment('sku编号');
            $table->string('sku_title',100)->nullable()->comment('sku名称');
            $table->unsignedInteger('number')->default(0)->comment('发货数量');
            $table->timestamps();

            $table->index('delivery_id');
            $table->index('oid');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('logistics_delivery_details') . "` comment '发货/退货单明细表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logistics_delivery_details');
    }
}
