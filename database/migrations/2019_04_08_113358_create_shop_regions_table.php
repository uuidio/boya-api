<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_regions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('region_name',100)->comment('地区名称');
            $table->unsignedInteger('parent_id')->default(0)->comment('父级id');
            $table->unsignedInteger('level')->default(1)->comment('等级');
            $table->unsignedInteger('is_leaf')->default(0)->comment('是否叶子节点');
            $table->unsignedTinyInteger('order_sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('disabled')->default(0)->comment('是否启用,启用1不启用');
            $table->timestamps();

            $table->index('region_name');
            $table->index('parent_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_regions') . "` comment '物流地区'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_regions');
    }
}
