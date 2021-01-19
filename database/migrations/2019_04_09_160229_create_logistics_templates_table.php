<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogisticsTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logistics_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->comment('商家店铺id');
            $table->string('name', 150)->comment('运费模板名称');
            $table->tinyInteger('is_free')->default('0')->comment('是否包邮,0-自定义运费,1-卖家承担运费');
            $table->enum('valuation',['1','2','3','4'])->default('1')->comment('运费计算类型,1-按重量,2-按件数,3-按金额,4-按体积');
            $table->tinyInteger('protect')->default(0)->comment('物流保价');
            $table->decimal('protect_rate', 10, 2)->default(0)->comment('保价费率');
            $table->decimal('minprice', 10, 2)->default(0)->comment('保价费最低值');
            $table->unsignedInteger('status')->default('1')->comment('是否开启,0-关闭,1-启用');
            $table->text('fee_conf')->nullable()->comment('运费模板中运费信息对象，包含默认运费和指定地区运费');
            $table->text('free_conf')->nullable()->comment('指定包邮的条件');
            $table->timestamps();

            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('logistics_templates') . "` comment '快递模板配置表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logistics_templates');
    }
}
