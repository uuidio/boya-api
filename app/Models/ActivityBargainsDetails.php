<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityBargainsDetails extends Model
{
    //
    protected $guarded = [];

    protected $appends = ['sold_count'];


    /**
     * 追加已售库存
     * @Author hfh_wind
     * @return string
     */
    public function getSoldCountAttribute()
    {
        $get_sold = ActivitiesRewardsSendLogs::where(['activities_id'=>$this->id,'type'=>'kanjia'])->count();

        return $get_sold;
    }



}
