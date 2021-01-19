<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('attr_name',100)->comment('属性名称');
            $table->tinyInteger('type_id')->default(0)->comment('所属类型id');
            $table->string('attr_value')->default(0)->comment('属性值列');
            $table->unsignedInteger('attr_show')->default(1)->comment('是否显示。0为不显示、1为显示');
            $table->unsignedInteger('attr_sort')->default(0)->comment('排序');

            $table->timestamps();

            $table->index('type_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_attributes') . "` comment '商品属性表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_attributes');
    }
}
