<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id(0为平台发布)');
            $table->string('name', 100)->comment('优惠券名称');
            $table->string('desc')->nullable()->comment('优惠券简介');
            $table->unsignedInteger('issue_num')->default(0)->comment('发行数量');
            $table->unsignedInteger('rec_num')->default(0)->comment('已领取数量');
            $table->unsignedInteger('user_num')->nullable()->default(1)->comment('会员可领取数量');
            $table->unsignedTinyInteger('scenes')->default(0)->comment('使用场景（1线上2线下）');
            $table->string('channel')->default('all')->comment('使用渠道');
            $table->unsignedTinyInteger('type')->default(0)->comment('使用类型（1满减券2折扣券3代金券）');
            $table->unsignedTinyInteger('discount')->default(0)->nullable()->comment('折扣（0为满减券）88=>8.8折');
            $table->decimal('denominations', 10, 2)->default(0)->nullable()->comment('面值（0为折扣券）');
            $table->decimal('origin_condition', 10, 2)->default(0)->nullable()->comment('满减条件（0为无门槛，满XX元可用）');
            $table->decimal('max_discount_fee', 10, 2)->default(0)->nullable()->comment('最高折扣金额(0为无上限)');
            $table->unsignedTinyInteger('is_single')->default(0)->comment('能否与其他券一起使用');
            $table->unsignedTinyInteger('is_bind_goods')->default(0)->comment('适用范围（0全商品,1部分商品适用,2部分商品不适用）');
            $table->unsignedTinyInteger('is_bind_shop')->default(0)->comment('适用范围（0全商铺,1部分商铺适用,2部分商铺不适用）');
            $table->string('limit_shop')->nullable()->comment('绑定的商铺');
            $table->string('limit_goods')->nullable()->comment('绑定的商品');
            $table->timestamp('get_star')->nullable()->comment('领取时间开始');
            $table->timestamp('get_end')->nullable()->comment('领取时间结束');
            $table->timestamp('start_at')->nullable()->comment('卡有效开始时间 ');
            $table->timestamp('end_at')->nullable()->comment('卡失效日期');
            $table->text('reason')->nullable()->comment('不可用原因');
            $table->timestamps();
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('coupons') . "` comment '优惠券表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupons');
    }
}
