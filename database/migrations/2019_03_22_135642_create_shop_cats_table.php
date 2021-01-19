<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateShopCatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_cats', function (Blueprint $table) {
            $table->increments('id')->comment('分类id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->string('class_icon')->nullable()->comment('分类图标');
            $table->unsignedInteger('parent_id')->default(0)->comment('店铺分类内父级id');
            $table->string('cat_path', 100)->nullable()->comment('分类路径(从根至本结点的路径,逗号分隔,首部有逗号)');
            $table->tinyInteger('level')->default(1)->nullable()->comment('分类层级（1：一级分类；2：二级分类）');
            $table->tinyInteger('is_leaf')->default(0)->comment('是否叶子结点（1：是；0：否）');
            $table->string('cat_name', 100)->comment('分类名称');
            $table->unsignedInteger('order_sort')->default(0)->nullable()->comment('排序');
            $table->tinyInteger('disabled')->default(0)->comment('是否屏蔽（1：是；0：否）');

            $table->timestamps();

            $table->index('parent_id');
            $table->index('cat_name');

        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_cats') . "` comment '店铺分类表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_cats');
    }
}
