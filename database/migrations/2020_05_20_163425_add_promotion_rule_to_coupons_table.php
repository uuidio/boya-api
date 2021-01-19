<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPromotionRuleToCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->smallInteger('fullminus_act_enabled')->default(1)->comment('满减活动是否可用,0-否,1-是');
            $table->smallInteger('discount_act_enabled')->default(1)->comment('满折活动是否可用,0-否,1-是');
            $table->smallInteger('group_act_enabled')->default(1)->comment('拼团活动是否可用,0-否,1-是');
            $table->smallInteger('seckill_act_enabled')->default(1)->comment('秒杀活动是否可用,0-否,1-是');
            $table->smallInteger('spread_goods_enabled')->default(1)->comment('分销商品是否可用,0-否,1-是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn([
                'fullminus_act_enabled',
                'discount_act_enabled',
                'group_act_enabled',
                'seckill_act_enabled',
                'spread_goods_enabled'
            ]);
        });
    }
}
