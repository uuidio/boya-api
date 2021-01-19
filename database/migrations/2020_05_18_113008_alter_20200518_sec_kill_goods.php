<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200518SecKillGoods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sec_kill_goods', function (Blueprint $table) {
            //
            $table->decimal('rewards', 10, 2)->default(0)->comment('返利金额');
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
        Schema::table('sec_kill_goods', function (Blueprint $table) {
            //
        });
    }
}
