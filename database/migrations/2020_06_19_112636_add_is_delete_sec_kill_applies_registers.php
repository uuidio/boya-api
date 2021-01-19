<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsDeleteSecKillAppliesRegisters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('sec_kill_applies_registers', function (Blueprint $table) {
           $table->unsignedTinyInteger('is_delete')->default(0)->nullable()->comment('是否删除：1是 0否');     
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sec_kill_applies_registers', function (Blueprint $table) {
            //
        });
    }
}
