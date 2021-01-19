<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter20200414UserAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_accounts', function (Blueprint $table) {
            //partner
            $table->unsignedInteger('partner_id')->default(0)->comment('合伙人id');
            $table->unsignedInteger('partner_role')->default(0)->comment('0-普通会员,1-推广员,2-小店,3-分销商,4-经销商');
            $table->unsignedInteger('partner_status')->default(0)->comment('0-正常,1-冻结,2-待审核');
//            $table->smallInteger('partner_level')->default(1)->comment('分销级别');//1-推广员,2-小店,3-分销商,4-经销商,3-冻结
            $table->text('pt_wx_mini_qr')->nullable()->comment('推广二维码分成线');
            $table->index('partner_id');
            $table->index('partner_role');
            $table->index('partner_status');
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
