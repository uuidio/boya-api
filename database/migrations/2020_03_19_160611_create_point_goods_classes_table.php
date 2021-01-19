<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointGoodsClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_goods_classes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('cat_name', 100)->comment('分类名称');
            $table->string('class_icon')->nullable()->comment('分类图标');
            $table->unsignedInteger('parent_id')->default(0)->comment('店铺分类内父级id');
            $table->string('cat_path', 100)->nullable()->comment('分类路径(从根至本结点的路径,逗号分隔,首部有逗号)');
            $table->tinyInteger('level')->default(1)->nullable()->comment('分类层级（1：一级分类；2：二级分类）');
            $table->tinyInteger('is_leaf')->default(0)->comment('是否叶子结点（1：是；0：否）');

            $table->unsignedTinyInteger('order')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_show')->default(1)->comment('是否显示，0为否，1为是，默认为1');
            $table->timestamps();

            $table->index('cat_name');
            $table->index('parent_id');
            $table->index('level');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('point_goods_classes') . "` comment '积分商品分类表'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_goods_classes');
    }
}
