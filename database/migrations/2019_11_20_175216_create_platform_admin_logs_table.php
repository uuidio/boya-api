<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformAdminLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_admin_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('admin_user_id')->comment('管理员id');
            $table->string('admin_user_name', 100)->comment('管理员用户名');
            $table->text('memo')->comment('操作内容');
            $table->unsignedTinyInteger('status')->default(0)->comment('操作内容,1-成功,0-失败');
            $table->text('router')->nullable()->comment('操作路由');
            $table->string('ip',30)->nullable()->comment('IP');
            $table->timestamps();

            $table->index('admin_user_id');
            $table->index('admin_user_name');
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('platform_admin_logs') . "` comment '平台操作日志'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('platform_admin_logs');
    }
}
