<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->string('ticket_image')->nullable()->comment('票券封面图');
            $table->string('ticket_name')->comment('票券名称');
            $table->string('ticket_code')->nullable()->comment('票码');
            $table->string('ticket_price')->default(0)->comment('票券价值金额');
            $table->string('ticket_source')->nullable()->comment('票券来源, kanjia砍价 choujiang抽奖');
            $table->string('ticket_status')->default(1)->comment('票券状态 1可用 0已使用 2已过期');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();

            $table->index('user_id');
            $table->index('ticket_code');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('user_tickets') . "` comment '会员票券表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_tickets');
    }
}
