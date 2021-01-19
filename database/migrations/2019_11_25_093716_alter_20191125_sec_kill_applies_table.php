<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20191125SecKillAppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sec_kill_applies', function (Blueprint $table) {
            //
            $table->unsignedTinyInteger('check_sign')->default(0)->comment('取消商家过期申请状态');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sec_kill_applies', function (Blueprint $table) {
            //
        });
    }
}
