<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLiveTagsImage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_tags_image', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('live_id')->comment('直播间id');
            $table->unsignedInteger('img_id')->comment('imgId');
            $table->text('location')->nullable()->comment('位置');
            $table->string('img',255)->nullable()->comment('图片');
            $table->timestamps();
            $table->index('img_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('live_tags_image');
    }
}
