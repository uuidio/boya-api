<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPointLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_point_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->enum('behavior_type',['obtain','consume'])->default('obtain')->comment('行为类型,obtain获得,consume消费');
            $table->string('behavior',100)->nullable()->comment('行为描述');
            $table->bigInteger('point')->default(0)->comment('积分值');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_point_logs') . "` comment '会员积分日志表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_point_logs');
    }
}
