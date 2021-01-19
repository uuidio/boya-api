<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200415UserDepositCashes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_deposit_cashes', function (Blueprint $table) {
            //
            $table->smallInteger('out_type')->default(1)->comment('提现类型,1-推广员,2-分销商');
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
