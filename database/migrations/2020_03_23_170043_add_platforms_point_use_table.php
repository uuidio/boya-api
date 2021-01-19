<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlatformsPointUseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gm_platforms', function (Blueprint $table) {
            $table->Integer('listorder')->default(100)->comment('权重');
            $table->unsignedInteger('open_point_exchange')->default(0)->comment('积分兑换开启状态');
            $table->string('use_obtain_point')->default('5|1')->comment('兑换比例,5积分换1积分');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gm_platforms', function (Blueprint $table) {
            //
        });
    }
}
