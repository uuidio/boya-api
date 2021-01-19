<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShopIdToIntegralBySelvesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('integral_by_selves', function (Blueprint $table) {
            //
            $table->unsignedInteger('shop_id')->nullable()->comment('店铺id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('integral_by_selves', function (Blueprint $table) {
            //
        });
    }
}
