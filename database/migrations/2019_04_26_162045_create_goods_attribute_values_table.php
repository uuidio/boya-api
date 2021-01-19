<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsAttributeValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_attribute_values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('attr_value_name',100)->comment('属性值名称');
            $table->unsignedInteger('attr_id')->comment('所属属性id');
            $table->unsignedInteger('type_id')->comment('类型id');
            $table->unsignedInteger('attr_value_sort')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('attr_id');
            $table->index('type_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_attribute_values') . "` comment '商品属性值表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_attribute_values');
    }
}
