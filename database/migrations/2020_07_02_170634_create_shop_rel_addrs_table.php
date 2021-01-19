<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopRelAddrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_rel_addrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->string('name')->nullable()->comment('收货人姓名');
            $table->string('tel', 20)->nullable()->comment('收货人手机号');
            $table->string('province', 50)->nullable()->comment('省份');
            $table->string('city', 50)->nullable()->comment('城市');
            $table->string('county', 50)->nullable()->comment('区县');
            $table->string('address')->comment('详细地址');
            $table->string('area_code')->nullable()->comment('地区编码，通过省市区选择获取');
            $table->string('postal_code')->nullable()->comment('邮政编码');
            $table->unsignedTinyInteger('is_default')->default(0)->comment('默认地址');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_rel_addrs') . "` comment '店铺回寄地址'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_rel_addrs');
    }
}
