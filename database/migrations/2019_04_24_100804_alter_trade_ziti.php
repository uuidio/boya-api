<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTradeZiti extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->unsignedTinyInteger('pick_type')->nullable()->default(0)->comment('提货方式(0快递1自提)');
            $table->string('pick_code')->nullable()->comment('提货码');
            $table->unsignedTinyInteger('pick_statue')->nullable()->default(0)->comment('提货状态(0未提货1已提货)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
