<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopRelBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_rel_brands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedTinyInteger('shop_id')->comment('店铺id');
            $table->unsignedTinyInteger('brand_id')->comment('品牌id');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('shop_rel_brands') . "` comment '店铺关联品牌表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_rel_brands');
    }
}
