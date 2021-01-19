<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTagsImage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags_image', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('live_id')->default(0)->comment('直播间id');
            $table->unsignedInteger('tag_id')->default(0)->comment('素材分类id');
            $table->string('img',255)->nullable()->comment('图片');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tags_image');
    }
}
