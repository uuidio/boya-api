<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopRelSellersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_rel_sellers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('shop_id')->comment('商家店铺id');
            $table->unsignedInteger('seller_id')->comment('商家账号');
            $table->string('shop_name')->comment('商家所属店铺名称');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('seller_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_rel_sellers') . "` comment '商家关联店铺账号表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_rel_sellers');
    }
}
