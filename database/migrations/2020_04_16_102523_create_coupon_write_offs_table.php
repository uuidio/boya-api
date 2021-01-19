<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponWriteOffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_write_offs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('bn')->comment('核销码');
            $table->unsignedInteger('shop_id')->default(0)->index()->comment('核销商家id');
            $table->unsignedInteger('source_shop_id')->default(0)->index()->comment('发布商家id');
            $table->decimal('money', 10, 2)->default(0)->comment('消费金额');
            $table->string('user_mobile',20)->nullable()->comment('客户手机号');
            $table->unsignedInteger('coupon_id')->index()->comment('优惠券id');
            $table->unsignedInteger('user_id')->index()->comment('会员id');
            $table->string('trade_no')->nullable()->comment('小票号');
            $table->text('remark')->nullable()->comment('备注');
            $table->string('voucher')->nullable()->default(0)->comment('凭证');
            $table->unsignedTinyInteger('status')->nullable()->default(0)->comment('状态，0待确认，1已确认，2已作废');
            $table->unsignedInteger('gm_id')->index()->comment('集团id');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('coupon_write_offs') . "` comment '线下优惠券核销记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_write_offs');
    }
}
