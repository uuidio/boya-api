<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPointActivityGradeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('point_activity_goods', function (Blueprint $table) {
            $table->tinyInteger('is_grade_limit')->nullable()->default(0)->comment('是否开启会员等级限制（0：否，1：是）');
            $table->string('grade_limit')->nullable()->comment('等级限制');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('point_activity_goods', function (Blueprint $table) {
            //
        });
    }
}
