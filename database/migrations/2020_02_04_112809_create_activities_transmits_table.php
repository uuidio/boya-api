<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTransmitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities_transmits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',100)->nullable()->comment('活动名称');
//            $table->string('QR',255)->comment('活动二维码');
//            $table->string('sign_up_img',255)->comment('已签到图');
//            $table->string('not_sign_img',255)->comment('未签到图');
//            $table->string('sign_up_txt',255)->comment('已签文字说明');
//            $table->string('not_sign_txt',255)->comment('未签到文字');
            $table->string('img',255)->nullable()->comment('活动图片');
            $table->text('note')->nullable()->comment('活动内容');
            $table->unsignedInteger('article_cat_id')->comment('文章分类id');
            $table->smallInteger('is_show')->default(1)->comment('是否开启,1-开启,0-关闭');
            $table->timestamps();

            $table->index('is_show');
            $table->index('article_cat_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('activities_transmits') . "` comment '传递活动设置表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities_transmits');
    }
}
