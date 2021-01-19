<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('seller_id')->comment('商家账号');
            $table->unsignedInteger('shop_id')->comment('商家店铺id');
            $table->string('company_name', 100)->comment('公司名称');
            $table->string('license_num', 100)->nullable()->comment('执照注册号');
            $table->string('license_img')->nullable()->comment('营业执照副本');
            $table->string('representative',20)->nullable()->comment('法定代表人姓名');
            $table->string('corporate_identity',20)->nullable()->comment('法人身份证号');
            $table->unsignedInteger('is_mainland')->default(1)->comment('法人身份,1代表中国大陆居民，2代表非中国大陆居民');
            $table->string('passport_number')->nullable()->comment('法人护照号');
            $table->string('corporate_identity_img')->nullable()->comment('法人身份证号电子版');
            $table->string('license_area')->nullable()->comment('营业执照所在地');
            $table->string('license_addr')->nullable()->comment('营业执照详细地址');
            $table->timestamp('establish_date')->nullable()->comment('成立日期');
            $table->timestamp('license_indate')->nullable()->comment('营业执照有效期');
            $table->string('enroll_capital',20)->nullable()->comment('注册资本');
            $table->string('scope')->nullable()->comment('经营范围');
            $table->string('shop_url',50)->nullable()->comment('公司官网');
            $table->string('company_area',100)->nullable()->comment('公司所在地');
            $table->string('company_addr',100)->nullable()->comment('公司地址');
            $table->string('company_phone',100)->nullable()->comment('公司电话');
            $table->string('company_contacts',100)->nullable()->comment('公司联系人');
            $table->string('company_cmobile',100)->nullable()->comment('公司联系人手机号');
            $table->string('tissue_code',100)->nullable()->comment('组织机构代码');
            $table->string('tissue_code_img',100)->nullable()->comment('组织机构代码副本');
            $table->string('tax_code',100)->nullable()->comment('税务登记号');
            $table->string('tax_code_img',100)->nullable()->comment('税务登记号副本');
            $table->string('bank_user_name',100)->nullable()->comment('银行开户公司名');
            $table->string('bank_name',100)->nullable()->comment('开户银行');
            $table->string('cnaps_code',100)->nullable()->comment('支行联行号');
            $table->string('bankID',50)->nullable()->comment('银行账号');
            $table->string('bank_area',100)->nullable()->comment('开户银行所在地');
            $table->timestamps();

            $table->index('seller_id');
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shop_infos') . "` comment '店铺入驻信息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_infos');
    }
}
