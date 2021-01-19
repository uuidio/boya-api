<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRelShopInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_rel_shop_infos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mobile',32)->nullable()->comment('手机号');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('gm_id')->default(1)->comment('集团id');
            $table->unsignedInteger('shop_id')->nullable()->comment('店铺id');
            $table->smallInteger('default')->default(1)->comment('是否为默认,1-是,0-否');
            $table->timestamps();

            $table->index('gm_id');
            $table->index('shop_id');
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_rel_shop_infos') . "` comment '会员切换店铺表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_rel_shop_infos');
    }
}
