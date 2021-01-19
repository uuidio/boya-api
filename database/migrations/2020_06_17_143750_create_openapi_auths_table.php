<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOpenapiAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('openapi_auths', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->comment('名称');
            $table->string('appid',20)->comment('身份码')->index('appid');
            $table->string('secret',64)->comment('密钥');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->string('api_auth')->default(0)->comment('api权限');
            $table->string('gm_auth')->default(0)->comment('项目权限');
            $table->timestamp('start')->nullable()->comment('生效时间');
            $table->timestamp('end')->nullable()->comment('失效时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('openapi_auths');
    }
}
