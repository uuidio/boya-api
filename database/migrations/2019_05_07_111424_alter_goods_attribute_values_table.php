<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterGoodsAttributeValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_attribute_values', function (Blueprint $table) {
            //
            $table->unsignedInteger('set_search')->default(0)->comment('是否缓存搜索');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods_attribute_values', function (Blueprint $table) {
            //
        });
    }
}
