<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_grades', function (Blueprint $table) {
            $table->increments('id');
            $table->string('grade_name',100)->comment('等级名称');
            $table->string('grade_logo')->nullable()->comment('会员等级LOGO');
            $table->string('experience')->default(0)->comment('所需成长值');
            $table->string('default_grade')->default(0)->comment('是否默认等级');
            $table->string('validity')->default(0)->comment('等级有效期');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_grades') . "` comment '会员等级表'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_grades');
    }
}
