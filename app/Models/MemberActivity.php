<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class MemberActivity extends Model
{
    public $guarded = [];
    public $appends = ['verify_name'];


    // 追加审核状态
    public function getVerifyNameAttribute(){
    	$status = ['0'=>'待审核','1'=>'审核通过','2'=>'驳回'];
    	return $status[$this->verify_status]??'';
    }

}
