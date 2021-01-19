<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->unsignedInteger('housing_id')->nullable()->comment('小区id');
            $table->string('housing_name')->nullable()->comment('小区id');
            $table->string('name')->comment('收货人姓名');
            $table->string('tel', 20)->comment('收货人手机号');
            $table->string('province', 50)->comment('省份');
            $table->string('city', 50)->comment('城市');
            $table->string('county', 50)->comment('区县');
            $table->string('address')->comment('详细地址');
            $table->string('area_code')->comment('地区编码，通过省市区选择获取');
            $table->string('postal_code')->nullable()->comment('邮政编码');
            $table->unsignedTinyInteger('is_default')->default(0)->comment('默认地址');
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_addresses') . "` comment '会员地址表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_addresses');
    }
}
