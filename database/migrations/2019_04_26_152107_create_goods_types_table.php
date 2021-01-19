<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type_name',100)->comment('类型名称');
            $table->tinyInteger('type_sort')->default(0)->comment('排序');
            $table->unsignedInteger('class_id')->default(0)->comment('所属分类id');
            $table->string('class_name',100)->default(0)->comment('所属分类名称');
            $table->timestamps();

            $table->index('class_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_types') . "` comment '商品类型表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_types');
    }
}
