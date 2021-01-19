<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_admins', function (Blueprint $table) {
            $table->increments('id')->comment('用户 id');
            $table->unsignedInteger('role_id')->default(0)->comment('用户角色');
            $table->string('username', 100)->comment('用户名');
            $table->string('email', 100)->nullable()->comment('用户邮件地址');
            $table->string('password', 100)->comment('用户密码');
            $table->unsignedTinyInteger('status')->nullable()->default(1)->comment('是否启用');
            $table->unsignedTinyInteger('is_root')->nullable()->default(0)->comment('是否是超级管理员');
            $table->rememberToken();
            $table->timestamps();

            $table->unique('username');
            $table->unique('email');
            $table->index('role_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('platform_admins') . "` comment '平台管理员账户表'");
        DB::statement("ALTER TABLE `" . prefixTableName('platform_admins') . "` AUTO_INCREMENT = 100000");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('platform_admins');
    }
}
