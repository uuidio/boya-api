<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberBenefitConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_benefit_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('page', 50)->comment('所属组');
            $table->string('group', 50)->comment('配置分组');
            $table->longText('value')->nullable()->comment('配置内容');
            $table->string('title')->nullable()->comment('配置标题');
            $table->unsignedTinyInteger('listorder')->default(0)->comment('排序');
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');

            $table->timestamps();

        });
        DB::statement("ALTER TABLE `" . prefixTableName('member_benefit_configs') . "` comment '会员权益配置表'"); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_benefit_configs');
    }
}
