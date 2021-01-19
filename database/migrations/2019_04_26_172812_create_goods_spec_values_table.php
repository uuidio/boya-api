<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsSpecValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_spec_values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sp_value_name',100)->comment('规格值名称');
            $table->tinyInteger('sp_id')->comment('所属规格id');
            $table->tinyInteger('cat_id')->comment('分类id');
            $table->tinyInteger('shop_id')->comment('店铺id');
            $table->string('sp_value_data',100)->nullable()->comment('规格');
            $table->unsignedInteger('sp_value_sort')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('sp_id');
            $table->index('cat_id');
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_type_brands') . "` comment '商品规格值表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_spec_values');
    }
}
