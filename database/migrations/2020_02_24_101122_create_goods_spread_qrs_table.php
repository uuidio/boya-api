<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsSpreadQrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_spread_qrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('goods_id')->comment('商品id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('wx_mini_goods_person_qr',255)->comment('商品个人推广二维码');
            $table->smallInteger('status')->default(0)->comment('状态,0-启用,1-不启用');
            $table->timestamps();

            $table->index('goods_id');
            $table->index('user_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('goods_spread_qrs') . "` comment '商品关联个人推广二维码表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goods_spread_qrs');
    }
}
