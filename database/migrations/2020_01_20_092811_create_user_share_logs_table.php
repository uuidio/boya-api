<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserShareLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_share_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('share_type')->nullable()->comment('分享内容类型');
            $table->bigInteger('fid')->default(0)->comment('存储活动的id或商品的id');
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_share_logs') . "` comment '会员分享记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_share_logs');
    }
}
