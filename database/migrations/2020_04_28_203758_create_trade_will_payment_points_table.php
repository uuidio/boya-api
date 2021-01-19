<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeWillPaymentPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_will_payment_points', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->text('order')->nullable()->comment('商品名集合');
            $table->string('type', 30)->comment('类型');
            $table->unsignedInteger('num')->default(0)->comment('买家消耗积分');
            $table->string('behavior',100)->nullable()->comment('行为描述');
            $table->string('remark')->nullable()->comment('备注');
            $table->string('log_type')->nullable()->comment('日志类型');
            $table->text('log_obj')->nullable()->comment('关联订单');
            $table->string('payment_id', 30)->comment('支付订单号');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->unsignedTinyInteger('status')->default(0)->comment('是否使用');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('user_point_logs') . "` comment '是否扣积分使用记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_will_payment_points');
    }
}
