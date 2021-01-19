<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsTypeBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_type_brands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('type_id')->comment('类型id');
            $table->unsignedInteger('brand_id')->comment('品牌id');
            $table->timestamps();

            $table->index('type_id');
            $table->index('brand_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('goods_type_brands') . "` comment '商品类型与品牌对应表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_type_brands');
    }
}
