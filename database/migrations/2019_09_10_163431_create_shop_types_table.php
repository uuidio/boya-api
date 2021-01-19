<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',100)->comment('类型名称');
            $table->string('shop_type',60)->comment('类型标识');
            $table->string('brief',200)->comment('类型描述');
            $table->string('suffix',60)->nullable()->comment('店铺名称后缀');
            $table->unsignedTinyInteger('max_item')->default(0)->comment('店铺默认商品上限');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态：1启用,0不启用');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('shop_types') . "` comment '店铺类型表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_types');
    }
}
