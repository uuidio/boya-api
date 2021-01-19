<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimeAfterPointGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('point_activity_goods', function (Blueprint $table) {
            $table->unsignedTinyInteger('allow_after')->default(1)->comment('是否允许发起售后');
            $table->timestamp('write_off_start')->nullable()->comment('核销开始时间');
            $table->timestamp('write_off_end')->nullable()->comment('核销结束时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('point_activity_goods', function (Blueprint $table) {
            //
        });
    }
}
