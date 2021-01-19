<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWxSubscribeMessagesAuthorizeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wx_subscribe_messages_authorize_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
//            $table->string('title', 100)->nullable()->comment('模板名称');
            $table->unsignedInteger('subscribe_id')->comment('模板id');
            $table->smallInteger('enable')->default(1)->comment('是否启用,1-启用,0-不启用');

            $table->timestamps();

            $table->index('user_id');
            $table->index('subscribe_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('wx_subscribe_messages_authorize_logs') . "` comment '会员授权订阅消息模板表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wx_subscribe_messages_authorize_logs');
    }
}
