<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAlbumClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('album_classes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('class_name', 100)->comment('相册名称');
            $table->unsignedInteger('shop_id')->comment('所属店铺id');
            $table->string('class_des')->nullable()->default(null)->comment('相册描述');
            $table->unsignedInteger('listorder')->nullable()->default(0)->comment('列表顺序');
            $table->string('class_cove')->nullable()->default(null)->comment('相册封面');
            $table->unsignedTinyInteger('is_default')->nullable()->default(0)->comment('是否为默认相册');
            $table->timestamps();

            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('album_classes') . "` comment '相册分类表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('album_classes');
    }
}
