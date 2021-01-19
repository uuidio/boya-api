<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewYitianUserToUserAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_accounts', function (Blueprint $table) {
            $table->unsignedInteger('new_yitian_user')->default(0)->comment('益田新用户');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_accounts', function (Blueprint $table) {
            //
        });
    }
}
