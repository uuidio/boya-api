<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecialActivityAppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_activity_applies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('act_id')->comment('活动id');
            $table->unsignedInteger('shop_id')->comment('店铺id');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态(0未审核1审核通过2审核不通过)');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('special_activity_applies') . "` comment '专题活动报名表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('special_activity_applies');
    }
}
