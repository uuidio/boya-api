<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('group_name',150)->nullable()->comment('拼团促销名称');
            $table->unsignedTinyInteger('type')->default(1)->comment('拼团活动类型:1=普通拼团');
            $table->string('group_desc',200)->nullable()->comment('拼团描述');
            $table->string('valid_grade',100)->nullable()->comment('会员级别集合');
            $table->unsignedBigInteger('group_size')->comment('拼团人数');
            $table->unsignedBigInteger('group_validhours')->comment('拼团有效期');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedBigInteger('goods_id')->comment('商品id');
            $table->unsignedBigInteger('sku_id')->comment('sku_id');
            $table->unsignedBigInteger('gc_id_3')->comment('商品三级分类id');
            $table->string('goods_name',150)->comment('商品名称');
            $table->decimal('price', 10, 2)->comment('商品原价');
            $table->string('spec_sign', 60)->nullable()->comment('规格标识');
            $table->decimal('group_price', 10, 2)->comment('拼团金额');
            $table->unsignedBigInteger('group_stock')->comment('拼团库存');
            $table->string('goods_image',200)->nullable()->comment('商品图片');
            $table->decimal('post_fee', 10, 2)->default(0)->comment('邮费');
            $table->string('promotion_tag',100)->nullable()->comment('促销标签');
            $table->unsignedBigInteger('group_times')->default(0)->comment('拼团次数');
            $table->timestamp('start_time')->nullable()->comment('拼团开始时间');
            $table->timestamp('end_time')->nullable()->comment('拼团结束时间');
            $table->unsignedBigInteger('sort')->default(0)->comment('排序');
            $table->unsignedBigInteger('is_show')->default(1)->comment('是否显示');
            $table->timestamps();

            $table->index('group_name');
            $table->index('shop_id');
            $table->index('goods_id');
            $table->index('sku_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('groups') . "` comment '拼团商品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups');
    }
}
