<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_classes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 200)->comment('分类名称');
            $table->string('cat_node', 200)->comment('分类节点');
            $table->unsignedInteger('parent_id')->default(0)->comment('父ID');
            $table->unsignedInteger('listorder')->nullable()->default(0)->comment('列表顺序');

            $table->timestamps();

            $table->index('parent_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('article_classes') . "` comment '文章分类表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_classes');
    }
}
