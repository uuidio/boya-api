<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->increments('id')->comment('店铺索引id');
            $table->string('shop_name', 100)->comment('店铺名称');
            $table->unsignedInteger('class_id')->default(0)->comment('店铺分类');
            $table->unsignedInteger('point_id')->default(0)->comment('点位ID');
            $table->unsignedInteger('housing_id')->default(0)->comment('小区ID');
            $table->unsignedInteger('grade_id')->default(0)->comment('店铺等级');
            $table->unsignedTinyInteger('shop_state')->default(1)->comment('店铺状态，0关闭，1开启，2审核中');
            $table->string('company_name', 150)->nullable()->comment('店铺公司名称');
            $table->unsignedInteger('province_id')->default(0)->comment('店铺所在省份ID');
            $table->unsignedInteger('city_id')->default(0)->comment('店铺所在城市ID');
            $table->unsignedInteger('area_id')->default(0)->comment('店铺所在区县ID');
            $table->unsignedBigInteger('street_id')->default(0)->comment('店铺所在街道ID');
            $table->string('province_name', 50)->nullable()->comment('店铺所在省份名称');
            $table->string('city_name', 50)->nullable()->comment('店铺所在城市名称');
            $table->string('area_name', 50)->nullable()->comment('店铺所在区县名称');
            $table->string('street_name', 50)->nullable()->comment('店铺所在街道名称');
            $table->string('address', 100)->nullable()->comment('详细地址');
            $table->string('zip_code', 10)->nullable()->comment('邮政编码');
            $table->unsignedInteger('listorder')->default(0)->comment('店铺排序');
            $table->string('shop_time', 10)->nullable()->comment('店铺时间');
            $table->string('shop_end_time', 10)->nullable()->comment('店铺关闭时间');
            $table->string('shop_logo')->nullable()->comment('店铺logo');
            $table->text('shop_banner')->nullable()->comment('店铺横幅');
            $table->string('shop_avatar')->nullable()->comment('店铺头像');
            $table->string('shop_phone', 20)->nullable()->comment('商家电话');
            $table->string('shop_keywords')->nullable()->comment('店铺seo关键字');
            $table->string('shop_description')->nullable()->comment('店铺seo描述');
            $table->unsignedTinyInteger('is_recommend')->default(0)->comment('推荐，0为否，1为是，默认为0');
            $table->unsignedInteger('shop_credit')->default(0)->comment('店铺信用');
            $table->unsignedInteger('shop_sales')->default(0)->comment('店铺销量');
            $table->text('shop_slide')->nullable()->comment('店铺幻灯片');
            $table->unsignedTinyInteger('is_own_shop')->default(0)->comment('是否自营店铺 1是 0否');
            $table->unsignedTinyInteger('area_type')->default(0)->comment('是否是社区店铺 1是 0否');
            $table->decimal('post_fee', 10, 2)->default(0)->comment('店铺配送费用');
            $table->longText('arrive_housing')->nullable()->comment('可配送小区');
            $table->enum('status',['none','active','locked','successful','failing','finish'])->default('none')->comment('申请状态,none-未提交,active-未审核,locked-审核中,successful-审核通过,failing-审核驳回,finish-开店完成');
            $table->string('shop_type',20)->default('flag')->comment('店铺类型,flag-品牌旗舰店,brand-品牌专卖店,cat-类目专营店,store-多品类通用型');
            $table->longText('reason')->nullable()->comment('审核不通过原因');
            $table->timestamps();

            $table->index('shop_name');
            $table->index('class_id');
            $table->index('shop_state');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('shops') . "` comment '店铺表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
