<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSetPartnersLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('set_partners_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->comment('会员id');
            $table->smallInteger('old_role')->default(0)->comment('修改前身份');
            $table->smallInteger('change_role')->default(0)->comment('修改后身份');
            $table->text('remarks')->nullable()->comment('备注');

            $table->timestamps();;
            $table->index('user_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('set_partners_logs') . "` comment '会员分销身份操作日志表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('set_partners_logs');
    }
}
