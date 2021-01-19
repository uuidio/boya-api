<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsUpdateInfoToWxUserinfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wx_userinfos', function (Blueprint $table) {
            $table->tinyInteger('is_update_info')->default(0)->comment('是否更新过会员资料 0-未更新 1-已更新');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wx_userinfos', function (Blueprint $table) {
            //
        });
    }
}
