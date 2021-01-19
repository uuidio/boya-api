<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccidToLives extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lives', function (Blueprint $table) {
            $table->string('im_token',128)->nullable()->comment('网易云信token');
            $table->string('accid',128)->nullable()->comment('网易云信id');
            $table->string('roomid',128)->nullable()->comment('聊天室id');
            $table->boolean('im_valid')->default(0)->comment('直播状态(0：关闭，1：开启)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lives', function (Blueprint $table) {
            //
        });
    }
}
