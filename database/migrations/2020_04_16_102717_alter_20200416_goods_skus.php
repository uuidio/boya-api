<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200416GoodsSkus extends Migration
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
            $table->decimal('profit_sharing', 10, 2)->default(0)->comment('分成金额');
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
