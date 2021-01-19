<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_accounts', function (Blueprint $table) {
            $table->increments('id')->comment('用户 id');
            $table->string('username', 100)->comment('商家账号');
            $table->unsignedInteger('role_id')->default(0)->comment('商家角色');
            $table->string('email', 50)->nullable()->comment('商家邮箱');
            $table->string('phone', 20)->nullable()->comment('商家手机号码');
            $table->string('password')->comment('用户密码');
            $table->unsignedTinyInteger('status')->nullable()->default(1)->comment('是否启用');
            $table->unsignedTinyInteger('seller_type')->default(0)->comment('商家账号类型 0:店主;1:店员;');
            $table->rememberToken();

            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('seller_accounts') . "` comment '商家账户表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_accounts');
    }
}
