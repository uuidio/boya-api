<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePageViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('obj_id')->nullable()->comment('对象ID 可能为gm_id 也可能为商品ID也可能为店铺ID 也可能为空');
            $table->string('type',50)->comment('首页为 index_fit  ');
            $table->string('visit_ip',50)->comment('访问者IP');
            $table->string('current_route',50)->comment('当前路由');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('page_views') . "` comment '页面浏览记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('page_views');
    }
}
