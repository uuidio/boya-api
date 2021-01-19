<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPasswordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_passwords', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->index()->comment('用户id');
            $table->string('pay_password', 60)->nullable()->comment('支付密码');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态：1正常 2锁定'); 
            $table->unsignedInteger('error_num')->default(0)->comment('输入错误次数'); 
            $table->timestamps();

            $table->unique('user_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('user_passwords') . "` comment '用户相关密码表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_passwords');
    }
}
