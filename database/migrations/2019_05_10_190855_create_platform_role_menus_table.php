<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformRoleMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_role_menus', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->index('role_id')->comment('角色ID');
            $table->unsignedInteger('menu_id')->index('menu_id')->comment('权限菜单ID');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('platform_role_menus') . "` comment '平台角色权限表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('platform_role_menus');
    }
}
