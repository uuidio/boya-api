<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_goods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('act_id')->comment('活动id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->string('goods_name')->nullable()->comment('商品名称');
            $table->string('goods_price')->nullable()->comment('商品价格');
            $table->string('goods_image')->nullable()->comment('商品图片');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('activity_goods') . "` comment '活动商品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_goods');
    }
}
