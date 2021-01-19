<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssistantUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assistant_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('login_account',100)->comment('用户名');
            $table->string('mobile',32)->nullable()->comment('手机号');
            $table->string('password',60)->comment('登录密码');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->string('img_url')->comment('助理头像');
            $table->string('username',100)->comment('昵称');
            $table->unsignedInteger('live_id')->default(0)->comment('直播间id');
            $table->unsignedInteger('anchor_id')->default(0)->comment('主播id');
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
        Schema::dropIfExists('assistant_users');
    }
}
