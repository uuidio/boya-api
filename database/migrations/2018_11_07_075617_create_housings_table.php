<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHousingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('housings', function (Blueprint $table) {
            $table->increments('id')->comment('住宅小区id');
            $table->string('housing_name', 100)->comment('住宅小区名称');
            $table->unsignedInteger('province_id')->comment('小区所在省份ID');
            $table->unsignedInteger('city_id')->comment('小区所在城市ID');
            $table->unsignedInteger('area_id')->comment('小区所在区县ID');
            $table->unsignedBigInteger('street_id')->comment('小区所在街道ID');
            $table->string('province_name', 50)->nullable()->comment('小区所在省份');
            $table->string('city_name', 50)->nullable()->comment('小区所在城市');
            $table->string('area_name', 50)->nullable()->comment('小区所在区县');
            $table->string('street_name', 50)->nullable()->comment('小区所在街道');
            $table->string('address', 100)->nullable()->comment('详细地址');
            $table->string('zip_code', 10)->nullable()->comment('邮政编码');
            $table->decimal('lng', 10, 7)->comment('经度');
            $table->decimal('lat', 10, 7)->comment('纬度');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('housings') . "` comment '住宅小区表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('housings');
    }
}
