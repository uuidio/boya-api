<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLevelUsersCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('yi_tian_user_cards', function (Blueprint $table) {
            $table->integer('level')->default(0)->comment('卡等级');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('yi_tian_user_cards', function (Blueprint $table) {
            //
        });
    }
}
