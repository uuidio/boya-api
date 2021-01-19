<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('area', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('pid')->default(0)->comment('父级id');
            $table->unsignedInteger('node')->default(0)->comment('父级节点');
            $table->string('name',60)->default(0)->comment('地点名称');
            $table->unsignedInteger('level')->default(0)->comment('等级');
            $table->string('lat',30)->default(0)->comment('纬度');
            $table->string('lng',30)->default(0)->comment('经度');
            $table->timestamps();

            $table->index('pid');
            $table->index('name');
            $table->index('level');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('area') . "` comment '地区表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('areas');
    }
}
