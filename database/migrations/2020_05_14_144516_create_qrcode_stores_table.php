<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQrcodeStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qrcode_stores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('md5key', 32)->index()->comment('内容加密作为下标');
            $table->text('content')->comment('二维码内容');
            $table->string('filesystem', 100)->nullable()->default('local')->comment('文件系统');
            $table->string('qrcode_url', 200)->comment('二维码图片路径');
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
        Schema::dropIfExists('qrcode_stores');
    }
}
