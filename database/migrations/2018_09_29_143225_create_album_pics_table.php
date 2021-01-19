<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAlbumPicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('album_pics', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pic_name', 100)->comment('图片名称');
            $table->unsignedInteger('class_id')->comment('相册id');
            $table->unsignedInteger('shop_id')->comment('所属店铺id');
            $table->string('filesystem', 100)->nullable()->default('local')->comment('文件系统');
            $table->string('pic_url', 200)->comment('图片路径');
            $table->string('pic_tag', 100)->nullable()->default(null)->comment('图片标签');
            $table->unsignedTinyInteger('is_use')->default(0)->comment('是否使用');
            $table->timestamps();

            $table->index('class_id');
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('album_pics') . "` comment '相册图片表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('album_pics');
    }
}
