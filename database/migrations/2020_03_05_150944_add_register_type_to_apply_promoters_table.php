<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRegisterTypeToApplyPromotersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apply_promoters', function (Blueprint $table) {
            //
            $table->string('register_type',20)->nullable()->default('per')->comment('注册类型(per：个人，pla：平台');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apply_promoters', function (Blueprint $table) {
            //
        });
    }
}
