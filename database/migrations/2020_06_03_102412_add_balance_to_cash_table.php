<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBalanceToCashTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_deposit_cashes', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->comment('余额');
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
                'balance',
            ]);
        });
    }
}
