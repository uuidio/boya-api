<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods', function (Blueprint $table) {
            $table->increments('id')->comment('商品id');
            $table->string('goods_name', 150)->comment('商品名称');
            $table->text('goods_info')->nullable()->comment('商品简介');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('gc_id')->comment('商品分类id');
            $table->unsignedInteger('gc_id_1')->comment('一级分类id');
            $table->unsignedInteger('gc_id_2')->comment('二级分类id');
            $table->unsignedInteger('gc_id_3')->comment('三级分类id');
            $table->unsignedInteger('brand_id')->nullable()->default(0)->comment('品牌id');
            $table->decimal('goods_price', 10, 2)->comment('商品价格');
            $table->decimal('goods_marketprice', 10, 2)->comment('市场价');
            $table->decimal('promotion_price', 10, 2)->default(0)->comment('商品促销价格');
            $table->unsignedTinyInteger('promotion_type')->default(0)->comment('促销类型 0无促销，1抢购，2限时折扣');
            $table->string('goods_serial', 150)->nullable()->comment('商品货号');
//            $table->unsignedInteger('goods_stock')->default(0)->comment('商品库存');
            $table->unsignedInteger('goods_stock_alarm')->default(0)->comment('库存报警值');
            $table->string('goods_barcode', 50)->nullable()->comment('商品条形码');
            $table->unsignedInteger('goods_click')->default(0)->comment('商品点击数量');
            $table->unsignedInteger('goods_salenum')->default(0)->comment('销售数量');
            $table->unsignedInteger('goods_collect')->default(0)->comment('收藏数量');
            $table->string('spec_name')->nullable()->comment('规格名称');
            $table->text('goods_spec')->nullable()->comment('商品规格序列化');
            $table->string('goods_image')->comment('商品主图');
            $table->text('goods_body')->comment('商品描述');
            $table->unsignedTinyInteger('goods_state')->default(0)->comment('商品状态 0下架，1正常，10违规（禁售）,20(已放入回收站)');
            $table->unsignedTinyInteger('goods_verify')->default(1)->comment('商品审核 1通过，0未通过，10审核中');
            $table->unsignedMediumInteger('transport_id')->default(0)->comment('运费模板id');
            $table->decimal('goods_freight', 10, 2)->default(0)->comment('运费');
            $table->unsignedTinyInteger('is_commend')->default(0)->comment('商品推荐 1是，0否 默认0');
            $table->string('goods_shop_c_lv1')->default(0)->comment('店铺分类一级id');
            $table->string('goods_shop_c_lv2')->default(0)->comment('店铺分类二级id');
            $table->unsignedTinyInteger('comment_star')->default(5)->comment('好评星级');
            $table->unsignedTinyInteger('comment_count')->default(0)->comment('评价数');
            $table->unsignedTinyInteger('is_virtual')->default(0)->comment('是否为虚拟商品 1是，0否');
            $table->timestamp('virtual_indate')->nullable()->comment('虚拟商品有效期');
            $table->unsignedTinyInteger('virtual_invalid_refund')->default(1)->comment('是否允许过期退款， 1是，0否');
            $table->unsignedTinyInteger('is_fcode')->default(0)->comment('是否为F码商品 1是，0否');
            $table->unsignedTinyInteger('is_presell')->default(0)->comment('是否是预售商品 1是，0否');
            $table->timestamp('presell_deliverdate')->nullable()->comment('预售商品发货时间');
            $table->unsignedTinyInteger('is_own_shop')->default(0)->comment('是否为平台自营');
            $table->unsignedTinyInteger('is_chain')->default(0)->comment('是否为门店商品 1是，0否');
            $table->decimal('invite_rate', 10, 2)->default(0)->comment('分销佣金');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('gc_id');
            $table->index('brand_id');
            $table->index('goods_name');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods') . "` comment '商品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods');
    }
}
