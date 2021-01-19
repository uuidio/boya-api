<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrmMasterStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_master_stores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('storeID')->nullable()->comment('店铺id');
            $table->string('storeCode')->index()->nullable()->comment('店铺编码');
            $table->string('storeName')->index()->nullable()->comment('店铺名称');
            $table->string('mallCode')->nullable()->comment('主体mallCode 如：101');
            $table->string('typeName')->nullable()->comment('类型');
            $table->string('pTypeName')->nullable()->comment('分类');
            $table->unsignedInteger('gm_id')->default(0)->index()->comment('集团id');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('crm_master_stores') . "` comment '维护crm所有店铺信息'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_master_stores');
    }
}
