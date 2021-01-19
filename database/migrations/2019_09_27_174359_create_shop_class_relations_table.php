<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopClassRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_class_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->comment('商家店铺id');
            $table->unsignedInteger('class_id')->comment('店铺分类id');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('class_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_class_relations') . "` comment '商家关联店铺分类表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_class_relations');
    }
}
