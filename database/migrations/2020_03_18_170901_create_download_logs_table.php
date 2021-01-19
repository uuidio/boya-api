<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDownloadLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('download_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type',36)->comment('类型');
            $table->string('desc',200)->nullable()->comment('备注');
            $table->string('url',255)->nullable()->comment('下载地址');
            $table->smallInteger('status')->default(0)->comment('是否生成,0-否,1-是');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id，0为平台');
            $table->unsignedInteger('gm_id')->default(1)->comment('集团id');

            $table->timestamps();

            $table->index('type');
            $table->index('status');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('download_logs') . "` comment '数据下载列表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('download_logs');
    }
}
