<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateYiTianUserCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yi_tian_user_cards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('card_code',150)->comment('卡代码');
            $table->string('card_name')->comment('卡名称');
            $table->string('card_img')->nullable()->comment('卡照片');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('group_manage_users') . "` comment '益田会员卡维护'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('yi_tian_user_cards');
    }
}
