<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->increments('id')->comment('用户 id');
            $table->unsignedInteger('role_id')->index()->comment('用户角色');
            $table->string('username', 100)->comment('用户名');
            $table->string('email', 100)->nullable()->comment('用户邮件地址');
            $table->string('password')->comment('用户密码');
            $table->unsignedTinyInteger('status')->nullable()->default(1)->comment('是否启用');
            $table->unsignedTinyInteger('is_root')->nullable()->default(0)->comment('是否是超级管理员');
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('admin_users') . "` comment '平台管理员账户表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_users');
    }
}
