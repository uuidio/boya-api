<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecialActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->comment('活动名称');
            $table->string('desc')->nullable()->comment('活动简介');
            $table->timestamp('star_apply')->nullable()->comment('报名开始时间');
            $table->timestamp('end_apply')->nullable()->comment('报名结束时间');
            $table->timestamp('star_time')->nullable()->comment('活动开始时间');
            $table->timestamp('end_time')->nullable()->comment('活动结束时间');
            $table->string('shop_type')->nullable()->comment('可报名的商家类型');
            $table->string('goods_class')->nullable()->comment('可报名的商品类型');
            $table->unsignedTinyInteger('type')->default(0)->comment('活动类型（1满减2折扣）');
            $table->string('range')->nullable()->comment('优惠范围');
            $table->decimal('limit', 10, 2)->default(0)->nullable()->comment('活动价格条件');
            $table->string('img')->nullable()->comment('宣传图');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('special_activities') . "` comment '专题活动表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('special_activities');
    }
}
