<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApplyArrowMemberActivitySkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('member_activity_skus', function (Blueprint $table) {
            $table->string('allow_apply_card')->nullable()->comment('允许报名的会员卡');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('member_activity_skus', function (Blueprint $table) {
            //
        });
    }
}
