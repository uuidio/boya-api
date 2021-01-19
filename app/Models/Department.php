<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['is_show_text'];//,'estimated_value','rewards_count'

    public function getIsShowTextAttribute()
    {
        return $this->is_show ? '是' : '否';
    }


    /**
     * 分销金额
     * @Author hfh_wind
     * @return string
     */
//    public function getEstimatedValueAttribute()
//    {
//        $res=ApplyPromoter::where('department_id',$this->id)->get();
//
//        $reward_value=0;
//        foreach($res  as $key =>$value){
//            $reward_value +=$value['estimated_count_all']['reward_value'];
//        }
//
//        return $reward_value;
//    }



    /**
     * 团队人数
     * @Author hfh_wind
     * @return string
     */
//    public function getCountSonAttribute()
//    {
//        $res=ApplyPromoter::where('department_id',$this->id)->count();
//        return $res;
//    }


    /**
     * 提成金额
     * @Author hfh_wind
     * @return string
     */
//    public function getRewardsCountAttribute()
//    {
//        $res=ApplyPromoter::where('department_id',$this->id)->get();
//        $reward_value=0;
//        foreach($res  as $key =>$value){
//            $reward_value +=$value['rewards_count']['reward_value'];
//        }
//        return $reward_value;
//    }
}
