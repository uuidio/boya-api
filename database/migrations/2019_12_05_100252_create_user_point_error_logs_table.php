<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPointErrorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_point_error_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('tid', 30)->comment('订单号');
            $table->enum('behavior_type',['obtain','consume'])->default('obtain')->comment('行为类型,obtain获得,consume消费');
            $table->bigInteger('point')->default(0)->comment('积分值');
            $table->string('message')->nullable()->comment('错误信息');
            $table->timestamps();

            $table->index('user_id');
            $table->index('tid');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_point_error_logs') . "` comment '会员积分错误日志表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_point_error_logs');
    }
}
