<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegralBySelvesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integral_by_selves', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('login_account',100)->nullable()->comment('用户名');
            $table->string('grade_name',100)->nullable()->comment('等级名称');
            $table->string('mobile')->comment('手机号');
            $table->string('ticket_id')->nullable()->comment('票据号');
            $table->string('address',200)->nullable()->comment('消费地点');
            $table->timestamp('invoice_at')->nullable()->comment('开票据时间');
            $table->decimal('fee', 10, 2)->nullable()->comment('消费金额');
            $table->longText('uploaded_data')->nullable()->comment('上传的图片');
            $table->string('status', 20)->default('ready')->comment('审核状态');
            $table->string('reject_reason')->nullable()->comment('驳回原因');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('integral_by_selves') . "` comment '自助积分相关表'");


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('integral_by_selves');
    }
}
