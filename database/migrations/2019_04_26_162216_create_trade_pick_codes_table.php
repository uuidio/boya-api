<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradePickCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_pick_codes', function (Blueprint $table) {
            $table->string('tid')->comment('订单号');
            $table->string('mobile')->comment('手机号');
            $table->string('pick_code',50)->comment('提货码');
            $table->index('pick_code');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_pick_codes') . "` comment '提货码关联表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_pick_codes');
    }
}
