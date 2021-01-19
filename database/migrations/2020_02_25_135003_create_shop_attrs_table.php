<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopAttrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_attrs', function (Blueprint $table) {

            //基础信息
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->comment('店铺id');

            //配置信息
            $table->boolean('promo_person')->default(0)->comment('推荐人功能(0：关闭，1：开启)');
            $table->boolean('promo_good')->default(0)->comment('推物功能(0：关闭，1：开启)');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_attrs') . "` comment '店铺配置附属表'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_attrs');
    }
}
