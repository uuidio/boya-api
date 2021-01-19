<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitiesRewardsSendLogs extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['user_name', 'activities_text','activities_reward_info','is_redeem_txt','lottery_info','gm_name','pick_type','pick_type_name'];


    /**
     * 会员名称
     * @Author hfh_wind
     * @return string
     */
    public function getUserNameAttribute()
    {
        $res=UserAccount::find($this->user_id);
        return $res['mobile']??'';
    }



    /**
     * 活动名称
     * @Author hfh_wind
     * @return string
     */
    public function getActivitiesTextAttribute()
    {
        $return=[];
        switch($this->type){
            case 'choujiang':
                $return = Lottery::where('id', $this->activities_id)->first();
                break;
            case 'kanjia':
                $return = ActivityBargainsDetails::where('id', $this->activities_id)->first();
                break;
            case 'zhuli':
                $return = ActivitiesTransmit::where('id', $this->activities_id)->first();
                break;
        }
        return $return;
    }


    /**
     * 奖品信息
     * @Author hfh_wind
     * @return string
     */
    public function getActivitiesRewardInfoAttribute()
    {

        if($this->type !='kanjia'){
            $res = ActivitiesRewards::where('id', $this->activities_reward_id)->first();
            return !empty($res)?$res:'';
        }
    }

    public function getPickTypeAttribute()
    {
        if (empty($this->tid)) {
            return -1;
        }
        $pick_type = Trade::where('tid',$this->tid)->value('pick_type');
        return $pick_type;
    }

    public function getPickTypeNameAttribute()
    {
        if (empty($this->tid)) {
            return '--';
        }
        if ($this->pick_type == 1) {
            return '自提';
        } else {
            return '快递';
        }
    }

    /**
     * 是否兑换
     * @Author hfh_wind
     * @return string
     */
    public function getIsRedeemTxtAttribute()
    {
        $status_text = '';
        switch ($this->is_redeem) {
            case '0':
                $status_text = '未兑换';
                break;
            case '1':
                $status_text = '已兑换,待发货';
                break;
            case '2':
                $status_text = '已发货';
                break;
        }

        return $status_text;
    }

    /**
     * 追加奖项名称
     *
     * @Author RJie
     * @return string
     */
    public function getLotteryInfoAttribute()
    {
        $res = ActivitiesRewards::where('id', $this->activities_reward_id)->first();
        if($res){
           $info = Lottery::find($res->activities_id);
           $name = $info['name'] ?? '';
        }

        return !empty($name)?$name:'';
    }
    /**
     * 追加项目名称
     * @Author swl
     * @return string
    */
    public function getGmNameAttribute()
    {
        $platform_name = GmPlatform::where('gm_id', '=', $this->gm_id)->value('platform_name');

        return !empty($platform_name) ? $platform_name : '';
    } 
}


