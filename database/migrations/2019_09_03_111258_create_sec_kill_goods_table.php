<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSecKillGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sec_kill_goods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 100)->nullable()->comment('秒杀');
            $table->string('goods_name', 100)->nullable()->comment('商品名称');
            $table->unsignedInteger('seckill_ap_id')->comment('秒杀活动id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('sku_id')->comment('商品id');
            $table->decimal('goods_price', 10, 2)->comment('店铺价格');
            $table->decimal('seckill_price', 10, 2)->comment('秒杀价格');
            $table->string('goods_image')->nullable()->comment('商品图片');
            $table->unsignedInteger('seckills_stock')->comment('秒杀库存');
            $table->unsignedInteger('stock_limit')->default(1)->comment('限购数量');
            $table->string('spec_sign',100)->nullable()->comment('sku标识');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->unsignedInteger('verify_status')->default(0)->comment('审核状态,0-待审核,1-审核被拒绝,2-审核通过');
            $table->string('activity_tag', 100)->nullable()->comment('活动标签');
            $table->timestamp('start_time')->nullable()->comment('秒杀开启时间');
            $table->timestamp('end_time')->nullable()->comment('秒杀结束时间');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('seckill_ap_id');
            $table->index('goods_id');
            $table->index('sku_id');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('created_at');
            $table->index('verify_status');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('sec_kill_goods') . "` comment '秒杀商品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sec_kill_goods');
    }
}
