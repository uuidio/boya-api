<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('coupon_id')->default(0)->comment('优惠券id');
            $table->bigInteger('num')->default(0)->comment('改变数量');
            $table->bigInteger('pre_num')->default(0)->comment('改变后的数量');
            $table->unsignedInteger('type')->default(1)->comment('1:增加2减少');
            $table->unsignedInteger('admin_user_id')->comment('管理员id');
            $table->string('admin_user_name', 100)->comment('管理员用户名');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_logs');
    }
}
