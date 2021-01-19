<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAllowAfterTradeOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('allow_after')->default(1)->comment('是否允许发起售后');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_orders', function (Blueprint $table) {
            //
        });
    }
}
