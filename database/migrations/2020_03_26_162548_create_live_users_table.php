<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLiveUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('login_account',100)->comment('用户名');
            $table->string('mobile',32)->nullable()->comment('手机号');
            $table->string('password',60)->comment('登录密码');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->string('img_url')->comment('主播头像');
            $table->string('username',100)->comment('昵称');
            $table->unsignedInteger('live_id')->default(0)->comment('直播间id');
            $table->unsignedInteger('assistant_id')->default(0)->comment('助理id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('live_users');
    }
}
