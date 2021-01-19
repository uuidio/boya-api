<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200428ApplyPromoters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apply_promoters', function (Blueprint $table) {
            //
            $table->string('checker_phone',36)->nullable()->comment('审核人手机');
            $table->unsignedInteger('checker_id')->default(0)->comment('审核人id');
            $table->timestamp('examine_time')->nullable()->comment('审核时间');
            $table->smallInteger('apply_role')->default(0)->comment('申请角色');

            $table->index('checker_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apply_promoters', function (Blueprint $table) {
            //
        });
    }
}
