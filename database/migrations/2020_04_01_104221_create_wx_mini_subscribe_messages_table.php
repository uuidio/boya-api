<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWxMiniSubscribeMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wx_mini_subscribe_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 100)->comment('标题');
            $table->string('template_id',100)->comment('模板id');
            $table->text('contents')->comment('详细内容');
            $table->text('data')->comment('发送内容');
            $table->string('page',100)->nullable()->comment('模板跳转');
            $table->string('description',100)->comment('场景说明');
            $table->string('miniprogram_state',30)->default('formal')->comment('跳转小程序类型,developer-开发版；trial-体验版；formal-正式版；默认为正式版');
            $table->smallInteger('enable')->default(1)->comment('是否启用,1-启用,0-不启用');

            $table->timestamps();
            $table->index('title');
            $table->index('template_id');
            $table->index('enable');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('wx_mini_subscribe_messages') . "` comment '订阅消息模板表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wx_mini_subscribe_messages');
    }
}
