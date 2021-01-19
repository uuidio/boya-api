<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEgoOldData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_accounts', function (Blueprint $table) {
            $table->unsignedInteger('old_source_id')->default(0)->comment('原ego会员id');
        });
        Schema::table('wx_userinfos', function (Blueprint $table) {
            $table->unsignedInteger('old_source_id')->default(0)->comment('原ego会员id');
        });
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->unsignedInteger('old_source_id')->default(0)->comment('原ego会员id');
        });
        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedInteger('ego_shop_id')->default(0)->comment('原ego店铺id');
        });
        Schema::table('goods', function (Blueprint $table) {
            $table->unsignedInteger('ego_goods_id')->default(0)->comment('原ego商品id');
        });
        

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
