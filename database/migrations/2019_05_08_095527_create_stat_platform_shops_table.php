<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatPlatformShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_platform_shops', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->string('shopname')->nullable()->comment('店铺名称');
            $table->decimal('shopaccountfee', 10, 2)->default(0)->comment('销售额');
            $table->unsignedInteger('shopaccountnum')->default(0)->comment('销售数量');

            $table->timestamps();

            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('stat_platform_shops') . "` comment '平台店铺销售排行统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_platform_shops');
    }
}
