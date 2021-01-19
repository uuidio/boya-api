<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('grade_id')->default(1)->comment('会员等级');
            $table->string('name',50)->nullable()->comment('昵称');
            $table->string('username',50)->nullable()->comment('真实姓名');
            $table->unsignedInteger('point')->default(0)->comment('积分');
            $table->string('refer_id',50)->nullable()->comment('来源');
            $table->string('refer_url',200)->nullable()->comment('推广来源URL');
            $table->integer('birthday')->nullable()->comment('会员生日');
            $table->unsignedTinyInteger('sex')->default(2)->comment('性别 0女，1男，2保密');
            $table->unsignedTinyInteger('wedlock')->default(0)->comment('婚姻状况 0未婚，1已婚');
            $table->string('education',30)->nullable()->comment('教育程度');
            $table->string('vocation',50)->nullable()->comment('职业');
            $table->string('reg_ip',16)->nullable()->comment('注册时IP地址');
            $table->string('cur',20)->nullable()->comment('货币(偏爱货币)');
            $table->string('lang',20)->nullable()->comment('偏好语言');
            $table->unsignedTinyInteger('disabled')->default(0)->comment('是否启用');
            $table->unsignedInteger('experience')->default(0)->comment('经验值');
            $table->enum('source',['pc','wap','weixin','api'])->default('wap')->comment('pc标准平台,wap手机触屏,weixin微信商城,API注册');
            $table->string('area',55)->nullable()->comment('地区');
            $table->string('addr')->nullable()->comment('地址');
            $table->unsignedTinyInteger('email_verify')->default(0)->comment('是否通过邮箱验证,0没有,1通过');
            $table->string('head_pic')->nullable()->comment('会员头像');
            $table->timestamps();

        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_profiles') . "` comment '商城会员用户表'");


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_profiles');
    }
}
