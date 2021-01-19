<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLongitudeLatitudeToGmPlatformTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gm_platforms', function (Blueprint $table) {
            $table->string('longitude',20)->nullable()->comment('经度');
            $table->string('latitude',20)->nullable()->comment('经度');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gm_platforms', function (Blueprint $table) {
            //
        });
    }
}
