<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitySendGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_send_goods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('act_id')->comment('活动id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->string('goods_name')->nullable()->comment('商品名称');
            $table->decimal('goods_price', 10, 2)->default(0)->nullable()->comment('商品价格');
            $table->string('goods_image')->nullable()->comment('商品图片');
            $table->unsignedTinyInteger('num')->default(1)->comment('数量');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('activity_send_goods') . "` comment '店铺活动赠送商品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_send_goods');
    }
}
