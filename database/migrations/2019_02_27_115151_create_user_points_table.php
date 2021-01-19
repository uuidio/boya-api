<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_points', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('point_count')->default(0)->comment('会员总积分值');
            $table->bigInteger('expired_point')->default(0)->comment('将要过期积分');
            $table->unsignedInteger('expired_time')->default(0)->comment('经验值过期时间');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_points') . "` comment '会员积分表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_points');
    }
}
