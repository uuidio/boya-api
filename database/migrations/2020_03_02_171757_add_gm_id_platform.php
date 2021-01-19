<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGmIdPlatform extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('platform_admin_logs', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('platform_roles', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('point_activity_goods', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('rate_traderate', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('sec_kill_applies', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('sec_kill_applies_registers', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('sec_kill_goods', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('sec_kill_orders', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
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
