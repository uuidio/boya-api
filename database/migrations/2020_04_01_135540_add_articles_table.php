<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('articles', function (Blueprint $table) {
            $table->string('subtitle',50)->nullable()->comment('副标题');
            $table->unsignedTinyInteger('title_is_show')->nullable()->default(1)->comment('是否显示标题，0为否，1为是');
            $table->string('article_img')->nullable()->default(null)->comment('文章配图');
            $table->unsignedInteger('activity_id')->nullable()->comment('自定义活动id');
            $table->unsignedTinyInteger('type')->nullable()->default(0)->comment('文章类型，0为文本，1为活动');
            $table->unsignedTinyInteger('verify_status')->nullable()->default(0)->comment('审核状态，0为待审核，1为审核通过，2审核不通过');
            $table->string('verify_remark',50)->nullable()->comment('审核备注');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
