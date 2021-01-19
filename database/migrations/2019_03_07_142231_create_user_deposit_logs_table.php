<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDepositLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_deposit_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('type')->default(0)->comment('日志类型:1增加2减少');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->string('operator', 30)->nullable()->comment('操作员');
            $table->decimal('fee', 10, 2)->comment('金额');
            $table->string('message')->nullable()->comment('变更记录');
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_deposit_logs') . "` comment '商城预存款记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_deposit_logs');
    }
}
