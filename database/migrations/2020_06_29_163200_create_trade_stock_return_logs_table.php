<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeStockReturnLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_stock_return_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tid', 30)->comment('订单号');
            $table->tinyInteger('status')->default(1)->comment('状态：1成功2失败');
            $table->string('reason')->nullable()->comment('失败原因');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_stock_return_logs');
    }
}
