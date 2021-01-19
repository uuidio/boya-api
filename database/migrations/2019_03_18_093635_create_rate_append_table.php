<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRateAppendTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_append', function (Blueprint $table) {
            $table->increments('append_rate_id');
            $table->unsignedInteger('rate_id')->comment('评价ID');
            $table->unsignedInteger('shop_id')->comment('店铺ID');
            $table->longText('append_content')->nullable()->comment('追评内容');
            $table->longText('append_rate_pic')->nullable()->comment('追评图片');
            $table->tinyInteger('is_reply')->default(0)->comment('商家是否已回复');
            $table->longText('append_reply_content')->nullable()->comment('追评回复内容');
            $table->string('reply_time', 10)->nullable()->comment('追评回复时间戳');
            $table->string('trade_end_time', 10)->nullable()->comment('订单完成时间');
            $table->tinyInteger('disabled')->default(0)->comment('是否有效');

            $table->index('rate_id');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('rate_append') . "` comment '订单追评表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rate_append');
    }
}
