<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGmPlatformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gm_platforms', function (Blueprint $table) {
            $table->bigIncrements('gm_id');
            $table->unsignedInteger('admin_id')->comment('平台项目管理员id');
            $table->string('admin_username', 100)->comment('平台项目管理员');
            $table->string('platform_name', 100)->comment('平台项目名称');
            $table->string('address', 100)->nullable()->comment('详细地址');
            $table->unsignedTinyInteger('status')->nullable()->default(1)->comment('是否启用');
            $table->string('base_uri')->nullable()->comment('');
            $table->string('app_id')->nullable()->comment('');
            $table->string('secret')->nullable()->comment('');
            $table->string('app_code')->nullable()->comment('');
            $table->string('corp_code')->nullable()->comment('');
            $table->string('org_code')->nullable()->comment('');
            $table->longText('platform_config')->nullable()->comment('配置信息');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('gm_platforms') . "` comment '平台项目信息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gm_platforms');
    }
}
