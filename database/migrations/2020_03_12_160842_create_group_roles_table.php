<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_roles', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('角色名称 id');
            $table->string('name', 100)->comment('角色名称');
            $table->unsignedTinyInteger('status')->default(1)->comment('是否启用');
            $table->unsignedTinyInteger('is_root')->default(0)->comment('是否为超级管理员组[1为是，0为否]');
            $table->string('remark')->nullable()->default('')->comment('备注');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->text('frontend_extend')->nullable()->comment('前端扩展字段');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('group_roles') . "` comment '集团角色表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_roles');
    }
}
