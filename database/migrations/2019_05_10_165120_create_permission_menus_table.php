<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_menus', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('菜单ID');
            $table->string('auth_provider')->index()->comment('系统权限认证模块');
            $table->unsignedInteger('parent_id')->default(0)->comment('父菜单id');
            $table->string('route_path', 150)->nullable()->comment('路由地址');
            $table->string('route_name', 150)->nullable()->comment('路由名称');
            $table->string('frontend_route_path', 150)->nullable()->comment('前端路由地址');
            $table->string('frontend_route_name', 150)->nullable()->comment('前端路由名称');
            $table->string('title', 150)->comment('菜单名称');
            $table->string('icon', 100)->nullable()->comment('菜单图标');
            $table->unsignedTinyInteger('hide')->default(0)->comment('是否隐藏菜单');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_dev')->default(0)->comment('仅开发者模式显示');
            $table->string('remark')->nullable()->comment('备注');
//            $table->unique('route_path', 'route_path');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('permission_menus') . "` comment '权限菜单表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission_menus');
    }
}
