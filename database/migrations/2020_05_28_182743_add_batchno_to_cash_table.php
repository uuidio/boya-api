<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchnoToCashTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_deposit_cashes', function (Blueprint $table) {
            $table->string('batch_no')->nullable()->comment('佣金宝发放批次号');
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
            //
        });
    }
}
