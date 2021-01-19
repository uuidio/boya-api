<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200420GoodsSpreadQrs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_spread_qrs', function (Blueprint $table) {
            //
            $table->smallInteger('type')->default(0)->comment('图片类型,0-推广商品图,1-推广中心');
            $table->bigInteger('gm_id')->default(0)->comment('项目id');

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods_spread_qrs', function (Blueprint $table) {
            //
        });
    }
}
