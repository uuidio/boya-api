<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20191206LogisticsDlycorpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logistics_dlycorps', function (Blueprint $table) {
            //
            $table->unsignedInteger('is_show')->default(1)->comment('是否展示,0-隐藏,1-展示');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('logistics_dlycorps', function (Blueprint $table) {
            //
        });
    }
}
