<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200226GoodsSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_skus', function (Blueprint $table) {
            //
            $table->decimal('rewards', 10, 2)->default(0)->comment('返利金额');
            $table->smallInteger('is_rebate')->default(0)->comment('是否开启,0-不开,1开启');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods_skus', function (Blueprint $table) {
            //
        });
    }
}
