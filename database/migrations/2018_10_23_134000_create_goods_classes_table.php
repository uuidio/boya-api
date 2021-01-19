<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_classes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('gc_name', 150)->comment('分类名称');
            $table->unsignedInteger('parent_id')->comment('父ID');
            $table->unsignedInteger('type_id')->nullable()->default(0)->comment('类型id');
            $table->unsignedTinyInteger('class_level')->default(1)->comment('分类级别');
            $table->string('type_name', 150)->nullable()->comment('类型名称');
            $table->float('commis_rate')->default(0)->comment('佣金比例');
            $table->unsignedTinyInteger('show_type')->default(0)->comment('商品展示方式');
            $table->unsignedTinyInteger('allow_virtual')->default(0)->comment('是否允许发布虚拟商品，1是，0否');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_show')->default(1)->comment('是否显示');
            $table->string('class_icon')->nullable()->comment('分类图标');
            $table->string('class_note', 100)->default(0)->comment('分类节点');
            $table->string('seo_title')->nullable()->comment('seo标题');
            $table->string('seo_keywords')->nullable()->comment('seo关键词');
            $table->string('seo_description')->nullable()->comment('seo描述');
            $table->timestamps();

            $table->index('parent_id');
        });
        
        DB::statement("ALTER TABLE `" . prefixTableName('goods_classes') . "` comment '商品分类表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_classes');
    }
}
