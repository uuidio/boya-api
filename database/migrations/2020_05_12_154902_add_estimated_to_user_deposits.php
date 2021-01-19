<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEstimatedToUserDeposits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_deposits', function (Blueprint $table) {
            $table->decimal('income', 10, 2)->default(0)->comment('现金收益');
            $table->decimal('estimated', 10, 2)->default(0)->comment('预估收益');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_deposits', function (Blueprint $table) {
            //
        });
    }
}
