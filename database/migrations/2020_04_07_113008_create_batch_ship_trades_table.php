<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBatchShipTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('batch_ship_trades', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('bs_id')->comment('批量发货id');
            $table->string('tid', 30)->comment('订单号');
            $table->string('logi_name',100)->nullable()->comment('物流公司名称');
            $table->string('logi_no',50)->nullable()->comment('物流单号');
            $table->smallInteger('status')->default(0)->comment('状态，1成功，0失败');
            $table->text('reason')->nullable()->comment('错误原因');
            $table->timestamps();
            $table->index('bs_id');
            $table->index('tid');
        });
        DB::statement("ALTER TABLE `" . prefixTableName('batch_ship_trades') . "` comment '批量导入发货订单记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('batch_ship_trades');
    }
}
