<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePanymentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('panyment_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('pay_gm_id')->comment('项目ID');
            $table->string('pay_type',30)->comment('支付类型');
            $table->string('pay_type_code',100)->comment('类型代码');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `" . prefixTableName('panyment_types') . "` comment '支付类型代码表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('panyment_types');
    }
}
