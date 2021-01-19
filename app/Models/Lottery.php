<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class Lottery extends Model
{
    protected $guarded = [''];

    protected $appends = ['status_name','type_name','activities_name','use_type_txt','is_show_text'];

    /**
     * 追加状态名称
     *
     * @Author RJie
     * @return mixed|string
     */
    public function getStatusNameAttribute()
    {
        $name = [
            0 => '未启用',
            1 => '已启用'
        ];

        return isset($this->status) ? $name[$this->status] : '';
    }

    /**
     * 追加奖品类型名称
     *
     * @Author RJie
     * @return mixed|string
     */
    public function getTypeNameAttribute()
    {
        $name = [
            0 => '谢谢惠顾',
            1 => '积分',
            2 => '票劵票',
            3 => '实物奖品',
            4 => '优惠劵',
        ];
        return isset($this->type) ? $name[$this->type] : '';
    }


    /**
     * 追加活动名称
     * @Author hfh_wind
     * @return string
     */
    public function getActivitiesNameAttribute()
    {
        $activity_name='';
        if($this->parent_id){
            $info=Lottery::find($this->parent_id);
            $activity_name=$info['name']??'';
        }
        return $activity_name;
    }

    /**
     * 转盘类型
     *
     * @Author RJie
     * @return mixed|string
     */
    public function getUseTypeTxtAttribute()
    {
        $txt = [
            0 => '线上转盘',
            1 => '线下多转盘',
        ];

        return isset($this->use_type) ? $txt[$this->use_type] : '';
    }

    /**
     * 展示状态
     *
     * @Author RJie
     * @return mixed|string
     */
    public function getIsShowTextAttribute()
    {
        $text = [
            0 => '隐藏',
            1 => '显示',
        ];

        return isset($this->is_show) ? $text[$this->is_show]:'';
    }

    /**
     *  等级限制转化
     *
     * @Author Huiho
     * @return mixed|string
     */
    public function getGradeLimitAttribute()
    {
        //return empty($this->attributes['grade_limit']) ? '--' : json_decode($this->attributes['grade_limit']);
        return empty($this->attributes['grade_limit']) ? '--' : explode(',', $this->attributes['grade_limit']);
    }
    
    public function getDeliveryTypeAttribute($value){
        return json_decode($value,true);
    }


}
