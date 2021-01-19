<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200218LotteriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lotteries', function (Blueprint $table) {
            //
            $table->string('wx_mini_page',255)->nullable()->comment('微信小程序路径page');
            $table->string('wx_mini_qr',255)->nullable()->comment('微信小程序二维码');
            $table->smallInteger('ticket_type')->default(0)->comment('票劵类型');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lotteries', function (Blueprint $table) {
            //
        });
    }
}
