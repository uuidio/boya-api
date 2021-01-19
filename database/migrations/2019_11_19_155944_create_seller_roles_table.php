<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_roles', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('角色名称 id');
            $table->string('name', 100)->comment('角色名称');
            $table->unsignedTinyInteger('status')->default(1)->comment('是否启用');
            $table->unsignedInteger('shop_id')->default(0)->comment('所属店铺的id');
            $table->string('remark')->nullable()->default('')->comment('备注');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->text('frontend_extend')->nullable()->comment('前端扩展字段');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('seller_roles') . "` comment '商家端角色表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_roles');
    }
}
