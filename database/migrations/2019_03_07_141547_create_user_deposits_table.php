<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_deposits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->decimal('deposit', 10, 2)->default(0)->comment('预存款余额');
            $table->string('password', 60)->nullable()->comment('支付密码');
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_deposits') . "` comment '商城预存款表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_deposits');
    }
}
