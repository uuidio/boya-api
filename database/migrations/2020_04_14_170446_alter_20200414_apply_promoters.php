<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200414ApplyPromoters extends Migration
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
            $table->string('shop_name',100)->nullable()->comment('店名');
            $table->string('address',200)->nullable()->comment('所在地址');
            $table->string('partner_mobile',30)->nullable()->comment('推荐人手机号');
            $table->unsignedInteger('partner_id')->default(0)->comment('分销商id');
            $table->smallInteger('partner_role')->default(0)->comment('分销级别');//1-推广员,2-小店,3-分销商,4-经销商,3-冻结
            $table->smallInteger('is_promoter')->default(0)->comment('是否推广员,1-是,0-否');//1-推广员,2-小店,3-分销商,4-经销商,3-冻结
            $table->text('remarks')->nullable()->comment('备注');

            $table->index('partner_id');
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
