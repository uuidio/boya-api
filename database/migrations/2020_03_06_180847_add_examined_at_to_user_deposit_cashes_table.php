<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExaminedAtToUserDepositCashesTable extends Migration
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
            $table->timestamp('examined_at')->nullable();
            $table->smallInteger('check_status')->default(0)->nullable();

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
