<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdUserYitianRelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_rel_yitian_infos', function (Blueprint $table) {
            $table->string('yitian_card_id',100)->nullable()->comment('益田的cardId');
            //
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
