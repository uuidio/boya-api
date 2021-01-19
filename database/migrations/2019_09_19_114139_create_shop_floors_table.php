<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopFloorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_floors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',100)->comment('楼层名称');
            $table->unsignedInteger('order')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_show')->default(1)->comment('是否显示，0为否，1为是，默认为1');
            $table->timestamps();

            $table->index('name');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_floors') . "` comment '店铺楼层表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_floors');
    }
}
