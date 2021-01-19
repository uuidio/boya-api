<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPushCrmLogToIntegralBySelvesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('integral_by_selves', function (Blueprint $table) {
            //
            $table->unsignedInteger('push_crm')->default(0)->comment('CRM推送状态 0未推 1已推 2成功 3失败');
            $table->text('crm_msg')->nullable()->comment('CRM推送返回信息');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('integral_by_selves', function (Blueprint $table) {
            //
        });
    }
}
