<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToUserTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_tickets', function (Blueprint $table) {
            //
            $table->text('desc')->nullable()->comment('票券说明');
            $table->unsignedTinyInteger('type')->default(1)->comment('类型 1电影票');
            $table->string('dead_line')->nullable()->comment('票券有效期');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_tickets', function (Blueprint $table) {
            //
        });
    }
}
