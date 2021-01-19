<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class MemberActivityApply extends Model
{
    public $guarded = [];

    public $appends = ['user_name','activity'];

    // 追加用户昵称
    public function getUserNameAttribute(){

    	$user = wxUserInfo::where('user_id',$this->user_id)->first();
    	return $user['nickname']??'';
    }

    // 追加活动名称
    public function getActivityAttribute(){
    
    	$activity = MemberActivity::where('id',$this->activity_id)->first();
    	return $activity['title']??'';
    }

}
