<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddArrivetimeToCashTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_deposit_cashes', function (Blueprint $table) {
            $table->timestamp('arrive_time')->nullable()->comment('到账时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_deposit_cashes', function (Blueprint $table) {
            $table->dropColumn([
                'arrive_time',
            ]);
        });
    }
}
