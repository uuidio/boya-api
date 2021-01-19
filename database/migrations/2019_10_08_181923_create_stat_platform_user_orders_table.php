<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatPlatformUserOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_platform_user_orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('user_id')->default(0)->comment('用户id');
            $table->string('user_name')->nullable()->comment('用户名');
            $table->decimal('userfee', 10, 2)->default(0)->comment('下单额');
            $table->unsignedInteger('userordernum')->default(0)->comment('下单量');
            $table->unsignedInteger('experience')->default(0)->comment('经验值');

            $table->timestamps();
            $table->index('created_at');
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE `em_stat_platform_users` comment'平台会员下单统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_platform_user_orders');
    }
}
