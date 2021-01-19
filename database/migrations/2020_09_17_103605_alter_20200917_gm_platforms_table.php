<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200917GmPlatformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gm_platforms', function (Blueprint $table) {
            //
            $table->string('app_url',255)->nullable()->comment('项目域名地址');
            $table->string('mini_appid',255)->nullable()->comment('微信小程序appid');
            $table->string('mini_secret',255)->nullable()->comment('微信小程序秘钥');
            $table->string('mch_id',255)->nullable()->comment('支付商户号');
            $table->string('pay_key',255)->nullable()->comment('微信支付签名秘钥');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gm_platforms', function (Blueprint $table) {
            //
        });
    }
}
