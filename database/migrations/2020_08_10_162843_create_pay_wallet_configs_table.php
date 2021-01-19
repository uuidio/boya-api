<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayWalletConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_wallet_configs', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->unique()->comment('集团id');
            $table->text('limit_shop')->nullable()->comment('限制的商铺-限制模式');
            $table->text('no_limit_shop')->nullable()->comment('限制的商铺-不限制模式');
            // 默认选择不适用模式
            $table->string('mode', 50)->default('limit')->comment('功能模式  limit  no_limit');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1开启  2关闭');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('pay_wallet_configs') . "` comment '钱包功能使用配置'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pay_wallet_configs');
    }
}
