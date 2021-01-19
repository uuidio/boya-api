<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitiesRewards extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['activities_text','goods_info','is_use_text'];


    /**
     * 活动名称
     * @Author hfh_wind
     * @return int|string
     */
    public function getActivitiesTextAttribute()
    {

        $return = '';
        switch ($this->type) {
            case 'zhuli':
                $res = ActivitiesTransmit::where('id', $this->activities_id)->first();
                $return = $res['name']??0;
                break;
            case 'kanjia':
                $res = ActivityBargains::where('id', $this->activities_id)->first();
                $return = $res['name']??0;
                break;
            case 'choujiang':
                //这里记录的id是抽奖活动商品记录的id,parent_id找才是
                $res = Lottery::where('id', $this->activities_id)->first();
                $return = $res['activities_name']??0;
                break;
        }
        return $return;
    }



    /**
     * 实物商品信息
     * @Author hfh_wind
     * @return string
     */
    public function getGoodsInfoAttribute()
    {
        $info=ActivitiesRewardGoods::find($this->activities_reward_goods_id);

        return $info;
    }



    /**
     * 是否启用
     * @Author hfh_wind
     * @return string
     */
    public function getIsUseTextAttribute()
    {
        return $this->is_use == 1 ? '启用' : '停用';
    }
}
