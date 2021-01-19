<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('payment_id', 30)->comment('支付单号');
            $table->decimal('amount', 10, 2)->comment('支付金额');
            $table->string('status', 20)->default('ready')->comment('支付状态');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('pay_type', 20)->default('online')->comment('支付类型');
            $table->string('pay_app', 20)->nullable()->comment('支付方式');
            $table->timestamp('payed_time')->nullable()->comment('支付完成时间');
            $table->text('memo')->nullable()->comment('支付注释');
            $table->unsignedTinyInteger('disabled')->default(0)->comment('支付单状态');
            $table->string('trade_no', 50)->default(0)->comment('支付单交易编号');

            $table->timestamps();
            $table->unique('payment_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('disabled');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('payments') . "` comment '支付记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
