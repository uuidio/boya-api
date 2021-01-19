<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccidToAssistantUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assistant_users', function (Blueprint $table) {
            $table->string('accid',128)->nullable()->comment('网易云信id');
            $table->string('roomid',128)->nullable()->comment('聊天室id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assistant_users', function (Blueprint $table) {
            //
        });
    }
}
