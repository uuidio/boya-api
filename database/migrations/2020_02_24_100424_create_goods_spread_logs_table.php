<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsSpreadLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_spread_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('pid')->comment('推广员id');
            $table->smallInteger('status')->default(0)->comment('状态,0-未购买,1-已过期,2-已购买');
            $table->string('tid',60)->nullable()->default(0)->comment('主订单号');
            $table->string('oid',60)->nullable()->default(0)->comment('子订单号');
            $table->timestamps();

            $table->index('goods_id');
            $table->index('user_id');
            $table->index('pid');
            $table->index('tid');
            $table->index('oid');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('goods_spread_logs') . "` comment '商品推广记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_spread_logs');
    }
}
