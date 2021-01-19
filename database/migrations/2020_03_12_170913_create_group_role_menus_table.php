<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupRoleMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_role_menus', function (Blueprint $table) {
           $table->unsignedInteger('role_id')->index('role_id')->comment('角色ID');
           $table->unsignedInteger('menu_id')->index('menu_id')->comment('权限菜单ID');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('group_role_menus') . "` comment '集团角色权限表'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_role_menus');
    }
}
