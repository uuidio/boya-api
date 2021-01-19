<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200423TradeEstimates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_estimates', function (Blueprint $table) {
            //
            $table->unsignedInteger('is_promoter')->default(0)->comment('推广员id');

            $table->index('is_promoter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_estimates', function (Blueprint $table) {
            //
        });
    }
}
