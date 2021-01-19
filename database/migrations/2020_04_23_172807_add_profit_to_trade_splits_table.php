<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProfitToTradeSplitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_splits', function (Blueprint $table) {
            //
            $table->decimal('profit', 10, 2)->default(0)->comment('利润额');
            $table->decimal('goods_cost', 10, 2)->default(0)->comment('成本价');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_splits', function (Blueprint $table) {
            //
        });
    }
}
