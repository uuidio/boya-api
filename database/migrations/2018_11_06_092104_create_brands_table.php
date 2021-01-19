<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->increments('id')->comment('品牌ID');
            $table->string('brand_name', 100)->comment('品牌名称');
            $table->string('brand_initial', 1)->nullable()->default('')->comment('品牌首字母');
            $table->unsignedInteger('class_id')->nullable()->default(0)->comment('分类ID');
            $table->string('brand_logo')->nullable()->default('')->comment('品牌LOGO');
            $table->text('description')->nullable()->comment('品牌描述');
            $table->unsignedTinyInteger('is_recommend')->nullable()->default(0)->comment('推荐，0为否，1为是，默认为0');
            $table->unsignedTinyInteger('show_type')->nullable()->default(0)->comment('品牌展示类型 0表示图片 1表示文字');
            $table->unsignedInteger('listorder')->nullable()->default(0)->comment('排序');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('brands') . "` comment '品牌表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brands');
    }
}
