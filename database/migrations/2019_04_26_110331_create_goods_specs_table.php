<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsSpecsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_specs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sp_name',100)->comment('规格名称');
            $table->tinyInteger('sp_sort')->default(0)->comment('排序');
            $table->unsignedInteger('class_id')->default(0)->comment('所属分类id');
            $table->string('class_name',100)->nullable()->comment('所属分类名称');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_specs') . "` comment '商品规格表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_specs');
    }
}
