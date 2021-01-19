<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogisticsDlycorpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logistics_dlycorps', function (Blueprint $table) {
            $table->increments('id');
            $table->string('corp_code',200)->comment('物流公司代码');
            $table->string('full_name',200)->comment('物流公司全名');
            $table->string('corp_name',200)->comment('物流公司简称');
            $table->string('website',200)->nullable()->comment('物流公司网址');
            $table->string('request_url',200)->nullable()->comment('查询接口网址');
            $table->unsignedTinyInteger('order_sort')->default(0)->comment('排序');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('logistics_dlycorps') . "` comment '物流公司表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logistics_dlycorps');
    }
}
