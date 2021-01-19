<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 200)->comment('标题');
            $table->unsignedInteger('cat_id')->default(0)->comment('分类id');
            $table->string('rule_url')->nullable()->default(null)->comment('图片');
            $table->unsignedTinyInteger('is_show')->nullable()->default(1)->comment('是否显示，0为否，1为是，默认为1');
            $table->text('content')->comment('内容');
            $table->unsignedInteger('listorder')->nullable()->default(0)->comment('列表顺序');
            $table->unsignedTinyInteger('type')->nullable()->default(0)->comment('规则类型，0为积分');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `" . prefixTableName('platform_rules') . "` comment '规则表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('platform_rules');
    }
}
