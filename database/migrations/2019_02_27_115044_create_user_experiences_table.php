<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserExperiencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_experiences', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->enum('behavior_type',['obtain','consume'])->default('obtain')->comment('行为类型,obtain获得,consume消费');
            $table->string('behavior',100)->nullable()->comment('行为描述');
            $table->bigInteger('experience')->default(0)->comment('成长值');
            $table->string('remark',100)->nullable()->comment('备注');
            $table->unsignedInteger('expiration_time')->default(0)->comment('经验值过期时间');
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_experiences') . "` comment '会员经验值详细记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_experiences');
    }
}
