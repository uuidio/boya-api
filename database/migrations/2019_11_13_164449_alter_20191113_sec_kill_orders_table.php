<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20191113SecKillOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sec_kill_orders', function (Blueprint $table) {
            //
            $table->unsignedInteger('quantity')->default(0)->comment('秒杀数量');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sec_kill_orders', function (Blueprint $table) {
            //
        });
    }
}
