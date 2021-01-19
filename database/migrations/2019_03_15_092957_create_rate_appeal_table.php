<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRateAppealTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_appeal', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rate_id')->comment('评价ID');
            $table->string('status', 7)->default('WAIT')->comment('状态值');
            $table->string('appeal_type', 12)->default('APPLY_UPDATE')->comment('申诉类型');
            $table->tinyInteger('appeal_again')->default(0)->comment('再次申诉');
            $table->longText('content')->comment('内容');
            $table->longText('evidence_pic')->comment('申诉图片凭证');
            $table->longText('reject_reason')->nullable()->comment('拒绝原因');
            $table->longText('appeal_log')->nullable()->comment('申诉日志');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('rate_appeal') . "` comment '商品相关统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rate_appeal');
    }
}
