<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSecKillAppliesRegistersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sec_kill_applies_registers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id');
            $table->unsignedInteger('seckill_ap_id')->default(0)->comment('活动id');
            $table->unsignedInteger('verify_status')->default(0)->comment('审核状态,0-待审核,1-审核被拒绝,2-审核通过');
            $table->unsignedInteger('valid_status')->default(1)->comment('有效状态,0-失效,1-有效');
            $table->string('refuse_reason', 200)->nullable()->comment('拒绝原因!');
            $table->timestamps();

            $table->index('shop_id');
            $table->index('seckill_ap_id');
            $table->index('verify_status');
            $table->index('valid_status');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('sec_kill_applies_registers') . "` comment '秒杀活动报名表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sec_kill_applies_registers');
    }
}
