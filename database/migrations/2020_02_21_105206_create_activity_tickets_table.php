<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('bargain_id')->default(0)->comment('砍价活动id');
            $table->unsignedInteger('bargain_progresses_id')->default(0)->comment('会员发起砍价活动记录id');
            $table->unsignedInteger('user_id')->default(0)->comment('会员id');
            $table->unsignedInteger('movie_id')->default(0)->comment('movie_id');
            $table->string('name')->nullable()->comment('兑换劵名称');
            $table->string('ticket_code',150)->comment('兑换码');
            $table->string('ticket_number',150)->nullable()->comment('查询码');
            $table->smallInteger('status')->default(0)->comment('状态,0-未兑换,1-已兑换');
            $table->string('type',100)->nullable()->comment('应用类型');
            $table->smallInteger('ticket_type')->default(0)->comment('票劵类型,0-默认电影票');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();

            $table->index('bargain_id');
            $table->index('bargain_progresses_id');
            $table->index('user_id');
            $table->index('ticket_code');
            $table->index('status');
            $table->index('type');
            $table->index('ticket_type');
            $table->index('movie_id');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('activity_tickets') . "` comment '电影兑换码表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_tickets');
    }
}
