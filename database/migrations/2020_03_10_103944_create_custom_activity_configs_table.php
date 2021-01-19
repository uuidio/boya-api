<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomActivityConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_activity_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->comment('自定义活动标题');
            $table->unsignedTinyInteger('status')->default(1)->comment('是否启用');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('custom_activity_configs') . "` comment '自定义活动列表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_activity_configs');
    }
}
