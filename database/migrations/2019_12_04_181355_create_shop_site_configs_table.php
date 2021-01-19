<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopSiteConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_site_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('page', 50)->comment('所属页面');
            $table->string('group', 50)->comment('配置分组');
            $table->json('value')->nullable()->comment('配置内容');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('page');
            $table->index('group');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('shop_site_configs') . "` comment '店铺配置表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_site_configs');
    }
}
