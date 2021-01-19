<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsMainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups_mains', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('group_name',150)->nullable()->comment('拼团促销名称');
            $table->string('group_desc',200)->nullable()->comment('拼团描述');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedBigInteger('goods_id')->comment('商品id');
//            $table->unsignedBigInteger('sku_id')->comment('sku_id');
            $table->unsignedBigInteger('gc_id_3')->comment('商品三级分类id');
            $table->string('goods_name',150)->comment('商品名称');
            $table->decimal('price', 10, 2)->comment('商品原价');
            $table->decimal('group_price', 10, 2)->comment('拼团金额');
            $table->string('goods_image',200)->nullable()->comment('商品图片');
            $table->string('promotion_tag',100)->nullable()->comment('促销标签');
            $table->unsignedBigInteger('group_size')->comment('拼团人数');
            $table->unsignedBigInteger('group_validhours')->comment('拼团有效期');
            $table->timestamp('start_time')->nullable()->comment('拼团开始时间');
            $table->timestamp('end_time')->nullable()->comment('拼团结束时间');
            $table->unsignedBigInteger('sort')->default(0)->comment('排序');
            $table->unsignedBigInteger('is_show')->default(1)->comment('是否显示');
            $table->decimal('rewards', 10, 2)->default(0)->comment('返利金额');
            $table->decimal('profit_sharing', 10, 2)->default(0)->comment('分成金额');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');

            $table->timestamps();

            $table->index('group_name');
            $table->index('shop_id');
            $table->index('goods_id');
        });
 
        DB::statement("ALTER TABLE `" . prefixTableName('groups_mains') . "` comment '拼团主表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups_mains');
    }
}
