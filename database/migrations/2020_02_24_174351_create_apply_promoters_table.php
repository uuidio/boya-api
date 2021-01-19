<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplyPromotersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apply_promoters', function (Blueprint $table) {

            //基础信息
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员ID');
            $table->string('real_name',100)->nullable()->comment('真实姓名');
            $table->unsignedInteger('job_number')->nullable()->comment('工号');
            $table->unsignedInteger('mobile')->nullable()->comment('手机号');
            $table->string('id_number',100)->nullable()->comment('身份证号');
            $table->string('department')->nullable()->comment('部门');
            $table->longText('id_photo')->nullable()->comment('身份证正反面');

            //配置信息
            $table->string('apply_status',20)->nullable()->default('apply')->comment('审核状态(apply：申请中，success: 成功 , fail: 失败)');
            $table->string('fail_reason')->nullable()->comment('失败原因');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('apply_promoters') . "` comment '推广员申请表'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apply_promoters');
    }
}
