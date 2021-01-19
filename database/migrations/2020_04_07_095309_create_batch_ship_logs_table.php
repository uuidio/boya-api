<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBatchShipLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('batch_ship_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type',36)->comment('类型');
            $table->string('url',255)->nullable()->comment('下载地址');
            $table->bigInteger('import_number')->default(0)->comment('导入条数');
            $table->bigInteger('succ_number')->default(0)->comment('成功条数');
            $table->smallInteger('status')->default(0)->comment('是否生成,0-否,1-是,2-失败');
            $table->unsignedInteger('shop_id')->default(0)->comment('店铺id，0为平台');
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('shop_id');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('batch_ship_logs') . "` comment '批量发货记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('batch_ship_logs');
    }
}
