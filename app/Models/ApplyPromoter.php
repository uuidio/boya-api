<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApplyPromoter extends Model
{
    //
    protected $guarded = [];
    protected $appends = [
        'estimated_count_all',
        'estimated_count_now',
        'rewards_count',
        'CountSon',
        'customer',
        'wx_info',
        'amount',
        'user_phone',
        'register_type_text',
        'is_promoter_text',
        'partner_role_text',
        'count_promoter',
        'checker_text',
        'apply_status_text'
    ];
    protected $hidden = ['id_photo'];
//    /**
//     * 身份证正面
//     * @Author huiho
//     * @return string
//     */
//    public function getIdPositiveAttribute()
//    {
//        $info = ApplyPromoter::where('id', $this->id)->value('id_photo');
//        $info = json_decode($info,true);
//        return !empty($info['p']) ? $info['p'] : '';
//    }
//    /**
//     * 身份证反面
//     * @Author huiho
//     * @return string
//     */
//    public function getIdOtherSideAttribute()
//    {
//        $info = ApplyPromoter::where('id', $this->id)->value('id_photo');
//        $info = json_decode($info,true);
//        return !empty($info['o']) ? $info['o'] : '';
//    }


    /**
     * 申请来源
     * @Author hfh_wind
     * @return string
     */
    public function getRegisterTypeTextAttribute()
    {
        $text = "未知";
        switch ($this->register_type) {
            case "per":
                $text = '个人申请';
                break;
            case "pla":
                $text = '平台授权';
                break;
            case "pt":
                $text = '小店推荐';
                break;
            case "dt":
                $text = '分销商推荐';
                break;
            case "dl":
                $text = '经销商推荐';
                break;
        }
        //distributor 分销商  dealer 经销商

        return $text;
    }


    /**
     * 申请状态
     *
     * @Author RJie
     * @return mixed|string
     */
    public function getApplyStatusTextAttribute()
    {
        $text = [
            'apply' => '申请中',
            'success' => '成功',
            'fail' => '失败',
        ];

        return $this->apply_status ? $text[$this->apply_status] : '';
    }

    /**
     * 所有预估收益统计
     * @Author hfh_wind
     * @return string
     */
    public function getEstimatedCountAllAttribute()
    {
//        $value=TradeEstimates::where('pid',$this->user_id)->where('status', 0)->sum('reward_value');
        $value = TradeEstimates::where('pid', $this->user_id)->where('status',
            0)->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct tid) as count ')->first();

        return !empty($value) ? $value : 0;
    }


    /**
     * 现预估收益统计
     * @Author hfh_wind
     * @return string
     */
    public function getEstimatedCountNowAttribute()
    {
        $value = TradeEstimates::where('pid', $this->user_id)->where(['status' => 0])->sum('reward_value');

        return !empty($value) ? $value : 0;
    }


    /**
     * 实际收益统计
     * @Author hfh_wind
     * @return string
     */
    public function getRewardsCountAttribute()
    {
        $value = TradeRewards::where('pid',
            $this->user_id)->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct tid) as count ')->first();

        return !empty($value) ? $value : 0;
    }


    /**
     * 下级统计
     * @Author hfh_wind
     * @return int
     */
    public function getCountSonAttribute()
    {
        $value = UserAccount::where('pid', $this->user_id)->count();

        return !empty($value) ? $value : 0;
    }


    /**
     * 客户数
     * @Author hfh_wind
     * @return array
     */
    public function getCustomerAttribute()
    {
//        $value= GoodsSpreadLogs::where(['pid' => $this->user_id])->selectRaw('count(distinct user_id) as total ')->first();
        $relatedLogs = RelatedLogs::where(['pid' => $this->user_id])->count();
        return $relatedLogs ? $relatedLogs : 0;
    }


    /**
     * 微信号信息
     * @Author hfh_wind
     * @return array
     */
    public function getWxInfoAttribute()
    {
        $value = WxUserinfo::where(['user_id' => $this->user_id])->selectRaw('user_id,nickname,headimgurl')->first();
        return !empty($value) ? $value : 0;
    }


    /**
     * 微信号信息
     * @Author hfh_wind
     * @return array
     */
    public function getUserPhoneAttribute()
    {
        $value = UserAccount::where(['id' => $this->user_id])->selectRaw('mobile')->first();
        return $value['mobile']??'';
    }


    /**
     * 微信号信息
     * @Author hfh_wind
     * @return array
     */
    public function getAmountAttribute()
    {
        $value = TradeEstimates::where('pid',
            $this->user_id)->selectRaw('GROUP_CONCAT(oid) as oids ')->where(['status' => 0])->first();

        if (!empty($value) && $value['oids']) {
            $oids = $value['oids'];
            //分销金额
            $seller_price = DB::select("select  SUM(amount) as  amount  FROM  em_trade_orders WHERE   oid in($oids) ");
            $amount = $seller_price[0]->amount??0;
        }

        return $amount??0;
    }


    /**
     * 是否推广员
     * @Author hfh_wind
     * @return array
     */
    public function getIsPromoterTextAttribute()
    {
        return $this->is_promoter ? '是' : '否';
    }


    /**
     * 追加角色
     * @Author hfh_wind
     * @return int
     */
    public function getPartnerRoleTextAttribute()
    {
        $text = '';
        switch ($this->partner_role) {
            case 1;
                $text = "推广员";
                break;
            case 2;
                $text = "小店";
                break;
            case 3;
                $text = "分销商";
                break;
            case 4;
                $text = "经销商";
                break;
        }
        return $text;
    }


    /**
     * 审核通过的记录不显示失败原因
     * @Author djw
     * @return int
     */
    public function getFailReasonAttribute($value)
    {
        if ($this->apply_status == 'success') {
            return '';
        }
        return $value;
    }


    /**
     * 客户数
     * @Author hfh_wind
     * @return array
     */
    public function getCountPromoterAttribute()
    {
        $pid = UserAccount::where(['partner_id' => $this->user_id])->count();
        return $pid;
    }


    /**
     *  审核类型
     * @Author hfh_wind
     * @return array
     */
    public function getCheckerTextAttribute()
    {
        return $this->checker_id ? "推荐人" : "平台";
    }
}
