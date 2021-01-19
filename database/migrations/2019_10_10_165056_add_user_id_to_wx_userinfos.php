<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserIdToWxUserinfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wx_userinfos', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedTinyInteger('user_type')->default(1)->comment('账号类型：1小程序,2公众号');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wx_userinfos', function (Blueprint $table) {
            //
        });
    }
}
