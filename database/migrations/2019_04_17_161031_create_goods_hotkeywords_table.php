<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsHotkeywordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_hotkeywords', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('keyword')->comment('搜索关键字');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->tinyInteger('disabled')->default(0)->comment('是否有效');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('goods_hotkeywords') . "` comment '商品热门搜索关键字'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_hotkeywords');
    }
}
