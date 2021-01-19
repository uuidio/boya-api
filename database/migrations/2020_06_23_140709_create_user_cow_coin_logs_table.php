<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCowCoinLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_cow_coin_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('before_gm_id')->comment('原积分集团ID');
            $table->integer('after_gm_id')->comment('现牛币集团ID');
            $table->integer('user_id')->comment('会员ID');
            $table->integer('before_point')->comment('原积分');
            $table->integer('before_cowcoin')->comment('原牛币');
            $table->integer('point')->comment('使用的积分');
            $table->integer('cowcoin')->comment('得到的牛币');
            $table->integer('after_point')->comment('现在的积分');
            $table->integer('after_cowcoin')->comment('现在的牛币');
            $table->decimal('parities', 8, 2)->comment('兑换率');
            $table->unsignedInteger('push_crm')->default(0)->comment('CRM订单推送状态');
            $table->text('crm_msg')->nullable()->comment('CRM订单推送返回信息');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_cow_coin_logs') . "` comment '积分转牛币记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_cow_coin_logs');
    }
}
