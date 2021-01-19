<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRelYitianInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_rel_yitian_infos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mobile',32)->nullable()->comment('手机号');
            $table->unsignedInteger('user_id')->index()->comment('会员id');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->string('card_type_code', 50)->nullable()->comment('卡类型代码');
            $table->string('card_code', 50)->nullable()->comment('卡号');
            $table->string('yitian_id',100)->nullable()->comment('益田的memberID');
            $table->bigInteger('yitian_point')->default(0)->comment('益田的积分');
            $table->unsignedInteger('new_yitian_user')->default(0)->comment('是否为项目新用户');
            $table->timestamps();
        });
        // 益田项目会员关联表
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_rel_yitian_infos');
    }
}
