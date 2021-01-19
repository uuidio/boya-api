<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelatedLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('related_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('pid')->comment('推广员id');
            $table->smallInteger('status')->default(0)->comment('状态,0-未关联,1-关联');
            $table->smallInteger('is_buy')->default(0)->comment('是否购买,0-否,1-是');
            $table->timestamps();

            $table->index('user_id');
            $table->index('pid');
            $table->index('status');
            $table->index('is_buy');
            $table->unique(['user_id','pid']);
        });
        DB::statement("ALTER TABLE `" . prefixTableName('related_logs') . "` comment '推广关系记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('related_logs');
    }
}
