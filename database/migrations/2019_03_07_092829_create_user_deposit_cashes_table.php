<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDepositCashesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_deposit_cashes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->decimal('amount', 10, 2)->comment('金额');
            $table->string('bank_card_id', 30)->comment('银行卡号');
            $table->string('bank_name', 50)->comment('开户行名称');
            $table->string('bank_card_owner', 20)->comment('持卡人姓名');
            $table->enum('status',['TO_VERIFY','VERIFIED','DENIED','COMPELETE'])->comment('提现状态,TO_VERIFY已申请,VERIFIED已审核,DENIED已驳回,COMPELETE已完成');
            $table->string('serial_id', 50)->comment('交易流水号');
            $table->string('executor', 50)->comment('转账执行人');

            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_deposit_cashes') . "` comment '预存款提现信息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_deposit_cashes');
    }
}
