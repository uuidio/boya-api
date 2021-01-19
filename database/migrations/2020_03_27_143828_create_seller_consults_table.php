<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerConsultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_consults', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',50)->nullable()->comment('姓名');
            $table->string('phone',20)->nullable()->comment('联系方式');
            $table->string('email',50)->nullable()->comment('邮箱');
            $table->string('company')->nullable()->comment('公司');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('seller_consults') . "` comment '商家入驻咨询'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_consults');
    }
}
