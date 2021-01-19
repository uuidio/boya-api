<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnerRelatedLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partner_related_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('推广员id');
            $table->unsignedInteger('partner_id')->comment('小店id');
            $table->smallInteger('type')->default(2)->comment('关联类型,2-小店,3-分销商,4-经销商');
            $table->smallInteger('status')->default(0)->comment('状态,0-未关联,1-关联');
            $table->smallInteger('is_own')->default(0)->comment('是否自绑,1-是,0-否');
            $table->text('remarks')->nullable()->comment('备注');
            $table->timestamps();

            $table->index('user_id');
            $table->index('partner_id');
            $table->index('type');
            $table->index('status');
            $table->unique(['user_id','partner_id']);
        });
        DB::statement("ALTER TABLE `" . prefixTableName('partner_related_logs') . "` comment '合伙人绑定记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partner_related_logs');
    }
}
