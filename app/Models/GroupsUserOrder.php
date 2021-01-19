<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class GroupsUserOrder extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['group_users','group_count'];




    /**
     * 团购组员
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getGroupUsersAttribute()
    {
        $groupUser = GroupsUserJoin::where(['groups_bn'=>$this->groups_bn,'status'=>'1'])->select('user_id','wechat_head_img','is_header','tid')->get();

        if(count($groupUser)>0){
            $groupUser=$groupUser->toArray();
        }
        return $groupUser;
    }




    /**
     * 已拼团数量
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getGroupCountAttribute()
    {
        $groupUser = GroupsUserJoin::where(['groups_bn'=>$this->groups_bn,'status'=>'1'])->count();

        return $groupUser;
    }
}
