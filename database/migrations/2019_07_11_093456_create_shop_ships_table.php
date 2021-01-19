<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopShipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_ships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',20)->nullable()->comment('名称');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->tinyInteger('type')->default(1)->comment('模板类型：1金额2重量3体积4按件数');
            $table->tinyInteger('add_type')->default(1)->comment('计算类型：1定额2累加');
            $table->string('rules')->comment('运费规则');
            $table->tinyInteger('default')->default(0)->comment('是否默认：0不通1默认');
            $table->tinyInteger('is_proctect')->default(0)->comment('是否提供保价服务：0否1是');
            $table->tinyInteger('proctect_rate')->default(0)->nullable()->comment('保价费率');
            $table->tinyInteger('status')->default(1)->comment('状态：0关闭1开启');
            $table->timestamps();
            $table->index('name');
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_ships') . "` comment '运费计算规则表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_ships');
    }
}
