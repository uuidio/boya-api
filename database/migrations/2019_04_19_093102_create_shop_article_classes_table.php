<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopArticleClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_article_classes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 200)->comment('分类名称');
            $table->unsignedInteger('parent_id')->default(0)->comment('父ID');
            $table->unsignedTinyInteger('listorder')->nullable()->default(0)->comment('列表顺序');
            $table->unsignedInteger('shop_id')->comment('商家店铺id');
            $table->timestamps();

            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_article_classes') . "` comment '商家文章分类表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_article_classes');
    }
}
