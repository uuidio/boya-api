<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentApisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_apis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('app',100)->comment('支付app')->unique();
            $table->string('name')->comment('支付中文名');
            $table->string('api')->comment('api接口');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_apis');
    }
}
