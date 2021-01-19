<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->comment('部门名称');
            $table->text('note')->nullable()->comment('备注');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_show')->default(1)->comment('是否显示，0为否，1为是，默认为1');
            $table->timestamps();
            $table->index('name');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('departments') . "` comment '部门表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('departments');
    }
}
