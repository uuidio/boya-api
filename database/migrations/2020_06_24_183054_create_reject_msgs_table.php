<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRejectMsgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reject_msgs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('term',200)->nullable()->comment('驳回消息术语');
            $table->unsignedTinyInteger('reject_status')->default(0)->comment('术语状态  0隐藏  1显示');
            $table->integer('reject_sort')->default(0)->comment('术语优先级  越大越靠前');
            $table->timestamps();

        });

        DB::statement("ALTER TABLE `" . prefixTableName('reject_msgs') . "` comment '驳回快捷消息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reject_msgs');
    }
}
