<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserRelYitianInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_rel_yitian_infos', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_update')->default(0)->comment('是否更新过数据');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_rel_yitian_infos', function (Blueprint $table) {
            //
        });
    }
}
