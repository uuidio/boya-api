<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('rel_id', 30)->comment('订单号');
            $table->unsignedInteger('op_id')->nullable()->comment('用户id');
            $table->string('op_name',100)->nullable()->comment('用户名');
            $table->string('op_role',9)->default('system')->comment('角色');
            $table->string('behavior',8)->default('update')->comment('行为');
            $table->longText('log_text')->comment('内容');
            $table->timestamps();

            $table->index('rel_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('trade_log') . "` comment '订单日志表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_log');
    }
}
