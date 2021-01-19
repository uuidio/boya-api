<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutocue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autocue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('live_id')->default(0)->comment('直播间id');
            $table->string('title', 50)->comment('标题');
            $table->string('antistop_one', 50)->comment('关键词一');
            $table->string('antistop_two', 50)->comment('关键词二');
            $table->string('antistop_three', 50)->comment('关键词三');
            $table->text('content')->nullable()->comment('内容');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
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
        Schema::dropIfExists('autocue');
    }
}
