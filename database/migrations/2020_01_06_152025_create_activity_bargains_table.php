<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityBargainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_bargains', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedTinyInteger('shop_id')->default(0)->comment('店铺id');
            $table->unsignedBigInteger('goods_id')->default(0)->comment('商品id');
            $table->string('title',150)->nullable()->comment('砍价标题');
            $table->string('goods_name',150)->nullable()->comment('商品名称');
            $table->string('bargains_image',250)->nullable()->comment('砍价图片');
//            $table->unsignedInteger('bargain_stock')->default(0)->comment('活动库存');
            $table->decimal('price', 10, 2)->comment('商品原价');
            $table->decimal('activity_money', 10, 2)->comment('商品活动价格');
//            $table->string('bargain_section', 20)->nullable()->comment('砍价区间');
//            $table->string('bargain_section_new', 20)->nullable()->comment('新用户砍价区间');
//            $table->unsignedBigInteger('bargain_validhours')->comment('砍价有效期');
//            $table->smallInteger('join_count')->comment('参与人数');
            $table->text('desc')->nullable()->comment('活动商品描述');
//            $table->timestamp('dead_line')->nullable()->comment('票卷有效期');
            $table->text('rule')->nullable()->comment('活动规则');
            $table->smallInteger('type')->default(0)->comment('类型,0-虚拟商品票劵,1-店铺商品,2-平台活动');
            $table->unsignedBigInteger('sort')->default(0)->comment('排序');
            $table->smallInteger('is_sold')->default(0)->comment('活动状态,0-待审核,1-上架,2-下架,3-作废,4-驳回');
            $table->string('bargain_number')->nullable()->comment('砍价活动编码');
            $table->text('reject_text')->nullable()->comment('驳回理由');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('goods_id');
            $table->index('is_sold');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('activity_bargains') . "` comment '砍价活动商品设置表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_bargains');
    }
}
