<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->increments('id')->comment('ID');
            $table->string('page', 50)->comment('所属页面');
            $table->string('group', 50)->comment('配置分组');
            $table->text('value')->nullable()->comment('配置内容');
            $table->unsignedInteger('listorder')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('page');
        });

        DB::statement("ALTER TABLE `" . prefixTableName('configs') . "` comment '网站配置表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configs');
    }
}
