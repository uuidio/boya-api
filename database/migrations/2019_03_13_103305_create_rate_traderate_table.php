<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRateTraderateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_traderate', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tid', 30)->comment('订单号');
            $table->string('oid', 30)->comment('子订单编号');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->unsignedInteger('shop_id')->comment('店铺ID');
            $table->unsignedInteger('goods_id')->comment('评论的商品ID');
            $table->string('goods_name', 60)->comment('评论的商品名称');
            $table->decimal('goods_price', 10, 2)->nullable()->comment('评论的商品价格');
            $table->string('goods_image')->nullable()->comment('评论的商品图片绝对路径');
            $table->longText('spec_nature_info')->nullable()->comment('sku描述');
            $table->string('result',7)->default('good')->comment('评价');
            $table->longText('content')->nullable()->comment('评价内容');
            $table->longText('rate_pic')->nullable()->comment('晒单图片');
            $table->tinyInteger('is_reply')->default(0)->comment('商家是否已回复');
            $table->longText('reply_content')->nullable()->comment('商家回复内容');
            $table->string('reply_time', 10)->nullable()->comment('商家回复时间戳');
            $table->tinyInteger('anony')->default(0)->comment('是否匿名');
            $table->string('role', 6)->default('buyer');
            $table->tinyInteger('is_lock')->default(1)->comment('该评价是否被锁定');
            $table->tinyInteger('is_append')->default(0)->comment('是否已追评');
            $table->tinyInteger('is_appeal')->default(1)->comment('是否可以申诉');
            $table->string('appeal_status', 9)->default('NO_APPEAL')->comment('申诉状态');
            $table->tinyInteger('appeal_again')->default(0)->comment('再次申诉');
            $table->string('appeal_time', 10)->nullable()->comment('申诉时间戳');
            $table->string('trade_end_time', 10)->nullable()->comment('订单完成时间');
            $table->tinyInteger('disabled')->default(0)->comment('是否有效');

            $table->timestamps();

            $table->index('user_id');
            $table->index('shop_id');
            $table->index('goods_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('rate_traderate') . "` comment '订单评价表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rate_traderate');
    }
}
