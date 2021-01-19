<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPicktypeToGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->string('pick_type',10)->default('0,1')->comment('提货类型：0快递1自提');
            $table->unsignedTinyInteger('trade_type')->default(1)->comment('交易类型：1普通2充值');
            $table->unsignedTinyInteger('is_need_qq')->default(0)->comment('是否需要QQ：0否1是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->dropColumn('pick_type');
            $table->dropColumn('trade_type');
            $table->dropColumn('is_need_qq');
        });
    }
}
