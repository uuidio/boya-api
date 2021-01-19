<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('login_account',100)->comment('用户名');
            $table->string('email',100)->nullable()->comment('会员邮箱');
            $table->string('mobile',32)->nullable()->comment('手机号');
            $table->string('password',60)->comment('登录密码');
            $table->string('login_type',60)->nullable()->comment('登录类型，信任登录或者普通登录');
            $table->unsignedTinyInteger('disabled')->default(0)->comment('是否启用');
            $table->unsignedInteger('grade_id')->default(1)->comment('会员等级id');
            $table->unsignedInteger('experience')->default(0)->comment('经验值');
            $table->timestamps();

            $table->unique('login_account');
            $table->unique('mobile');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_accounts') . "` comment '商城会员用户表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_accounts');
    }
}
