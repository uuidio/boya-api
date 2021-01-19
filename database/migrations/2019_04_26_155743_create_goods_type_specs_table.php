<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsTypeSpecsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_type_specs', function (Blueprint $table) {
//            $table->bigIncrements('id');
            $table->unsignedInteger('type_id')->comment('类型id');
            $table->unsignedInteger('sp_id')->comment('规格id');
            $table->timestamps();

            $table->index('type_id');
            $table->index('sp_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_type_specs') . "` comment '商品类型与规格对应表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_type_specs');
    }
}
