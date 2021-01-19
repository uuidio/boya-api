<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 200)->comment('标题');
            $table->unsignedInteger('cat_id')->comment('分类id');
            $table->string('article_url')->nullable()->default(null)->comment('图片');
            $table->unsignedTinyInteger('is_show')->nullable()->default(1)->comment('是否显示，0为否，1为是，默认为1');
            $table->text('content')->comment('内容');
            $table->unsignedTinyInteger('listorder')->nullable()->default(0)->comment('列表顺序');
            $table->unsignedInteger('shop_id')->comment('商家店铺id');
            $table->timestamps();

            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_articles') . "` comment '商家文章表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_articles');
    }
}
