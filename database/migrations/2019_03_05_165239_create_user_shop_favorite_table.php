<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserShopFavoriteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_shop_favorite', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->string('shop_name', 100)->comment('店铺名称');
            $table->string('shop_logo')->nullable()->comment('店铺logo');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_shop_favorite') . "` comment '会员收藏店铺表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_shop_favorite');
    }
}
