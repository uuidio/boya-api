<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatPlatformUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_platform_users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('newuser')->default(0)->comment('新增会员数');
            $table->unsignedInteger('accountuser')->default(0)->comment('会员数总数');
            $table->unsignedInteger('shopnum')->default(0)->comment('新增店铺数');
            $table->unsignedInteger('shopaccount')->default(0)->comment('店铺数');
            $table->unsignedInteger('sellernum')->default(0)->comment('新增商家数');
            $table->unsignedInteger('selleraccount')->default(0)->comment('商家数');
            
            $table->timestamps();

            $table->index('created_at');

        });


        DB::statement("ALTER TABLE `em_stat_platform_users` comment'平台会员统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_platform_users');
    }
}
