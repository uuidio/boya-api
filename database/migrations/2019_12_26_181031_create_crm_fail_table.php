<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrmFailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_fail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('uid')->comment('用户id');
            $table->string('api', 150)->nullable()->comment('访问api');
            $table->string('params', 255)->nullable()->comment('访问参数');
            $table->string('fail_reason', 255)->nullable()->comment('报错信息');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('crm_fail') . "` comment '推送crm错误日志'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_fail');
    }
}
