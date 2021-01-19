<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerRoleMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_role_menus', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->index('role_id')->comment('角色ID');
            $table->string('menu_name')->comment('权限菜单');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('seller_role_menus') . "` comment '商家端角色权限表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_role_menus');
    }
}
