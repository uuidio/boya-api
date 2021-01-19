<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUVTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('u_v_trades', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->index()->comment('店铺id');
            $table->unsignedInteger('trading_volume')->comment('交易人数');
            $table->date('trading_day')->comment('交易日');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();


        });
        DB::statement("ALTER TABLE `" . prefixTableName('u_v_trades') . "` comment '成交UV报表'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('u_v_trades');
    }
}
