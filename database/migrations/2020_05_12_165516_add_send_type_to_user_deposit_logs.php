<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSendTypeToUserDepositLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_deposit_logs', function (Blueprint $table) {
            $table->smallInteger('send_type')->default(1)->comment('转账类型,1-线上微信,2-线下');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_deposit_logs', function (Blueprint $table) {
            //
        });
    }
}
