<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20191031TradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            //以目前单个秒杀团购立即购买的情况下,主表也记录,方便查看
            $table->string('activity_sign',30)->nullable()->comment('活动标识,活动id');
            $table->unsignedInteger('activity_sign_id')->default(0)->comment('活动标识,活动id');

            $table->index('activity_sign');
            $table->index('activity_sign_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trades', function (Blueprint $table) {
            //
        });
    }
}
