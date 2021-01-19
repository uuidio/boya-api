<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradePaybillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_paybills', function (Blueprint $table) {
            $table->increments('id');
            $table->string('payment_id', 30)->comment('支付单号');
            $table->string('tid', 30)->comment('订单编号');
            $table->string('status', 20)->default('ready')->comment('支付状态');
            $table->decimal('amount', 10, 2)->comment('支付金额');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->timestamp('payed_time')->nullable()->comment('支付完成时间');

            $table->timestamps();

            $table->index('status');
            $table->index('tid');
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_paybills') . "` comment '订单支付单据记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_paybills');
    }
}
