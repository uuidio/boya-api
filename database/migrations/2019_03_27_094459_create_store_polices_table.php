<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStorePolicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_polices', function (Blueprint $table) {
            $table->increments('id')->comment('id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedInteger('policevalue')->default(0)->comment('报警值');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_cats') . "` comment '店铺商品报警表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_polices');
    }
}