<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStateToSellerConsults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('seller_consults', function (Blueprint $table) {
            $table->tinyInteger('state')->nullable()->default(0)->comment('处理状态（0：未处理，1：处理中，2：已处理）');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('seller_consults', function (Blueprint $table) {
            //
        });
    }
}
