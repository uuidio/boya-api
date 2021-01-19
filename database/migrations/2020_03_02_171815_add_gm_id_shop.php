<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGmIdShop extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('seller_accounts', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('shop_articles', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('shop_floors', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('shop_rel_cats', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('shop_rel_sellers', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedInteger('gm_id')->default(1)->index()->comment('集团id');
        });
        Schema::table('site_configs', function (Blueprint $table) {
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
