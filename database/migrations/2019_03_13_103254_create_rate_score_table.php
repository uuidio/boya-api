<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRateScoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_score', function (Blueprint $table) {
            $table->string('tid', 30)->comment('订单id');
            $table->unsignedInteger('cat_id')->comment('关联类目id');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->unsignedInteger('shop_id')->comment('店铺ID');
            $table->smallInteger('tally_score')->nullable()->comment('描述相符的评分');
            $table->smallInteger('attitude_score')->nullable()->comment('服务态度的评分');
            $table->smallInteger('delivery_speed_score')->nullable()->comment('发货速度的评分');
            $table->smallInteger('logistics_service_score')->nullable()->comment('物流服务的评分');
            $table->tinyInteger('disabled')->default(0)->comment('是否有效');

            $table->timestamps();

            $table->index('user_id');
            $table->index('shop_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('rate_score') . "` comment '店铺评价表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rate_score');
    }
}
