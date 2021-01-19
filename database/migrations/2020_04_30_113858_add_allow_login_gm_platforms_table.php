<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAllowLoginGmPlatformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gm_platforms', function (Blueprint $table) {
            $table->unsignedInteger('allow_login')->default(1)->comment('是否允许登录');
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
