<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopZitisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_zitis', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->string('address')->comment('商品供应链接');
            $table->unsignedTinyInteger('statue')->nullable()->default(1)->comment('是否可用，0为不可用，1为可用，默认为1');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_zitis') . "` comment '自提地址列表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_zitis');
    }
}
