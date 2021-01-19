<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentCfgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_cfgs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->comment('支付方式名称');
            $table->string('pay_type', 20)->comment('支付类型');
            $table->string('describe')->nullable()->comment('描述');
            $table->enum('platform',['ispc','isapp','iswap','iscommon'])->default('iswap')->comment('支持平台--pc标准平台,isapp手机端,iswap 手机H5,iscommon通用');
            $table->string('configure')->nullable()->comment('配置');
            $table->unsignedInteger('orderby')->default(0)->comment('排序');
            $table->unsignedTinyInteger('on_use')->default(0)->comment('是否开启此支付方式,0否,1是');
            $table->unsignedTinyInteger('default')->default(0)->comment('设为默认支付方式,0否,1是');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('payment_cfgs') . "` comment '商城支付配置表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_cfgs');
    }
}
