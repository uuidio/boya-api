<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeActivityDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_activity_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tid', 30)->comment('订单号');
            $table->string('oid', 30)->comment('子订单编号');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->unsignedInteger('activity_id')->comment('促销规则id');
            $table->unsignedInteger('goods_id')->comment('商品ID');
            $table->unsignedInteger('sku_id')->comment('sku的ID');
            $table->string('activity_type', 30)->comment('优惠类型');
            $table->string('rule')->comment('活动规则');
            $table->string('activity_tag', 30)->nullable()->comment('促销标签');
            $table->string('activity_name', 255)->nullable()->comment('促销名称');
            $table->longText('activity_desc')->nullable()->comment('促销描述');
            $table->timestamps();

            $table->index('tid');
            $table->index('oid');
            $table->index('user_id');
            $table->index('activity_id');
            $table->index('goods_id');
            $table->index('sku_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_activity_details') . "` comment '订单的参与活动记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_activity_details');
    }
}
