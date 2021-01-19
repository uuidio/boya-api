<?php

namespace ShopEM\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];
    protected $appends = ['status_text', 'is_hand_push_text', 'type_text', 'scenes_text','gm_name','is_distribute_text','act_rule_text', 'used_num'];

    public function getStatusTextAttribute()
    {
        $tradeStatusMap = [
            'WAIT'           => '待审核',    // 待审核
            'SUCCESS'   => '通过',    // 通过
            'FAILS' => '驳回',  // 驳回
        ];
        return isset($tradeStatusMap[$this->status]) ? $tradeStatusMap[$this->status] : '';
    }

    public function getIsHandPushTextAttribute()
    {
        return $this->is_hand_push ? '是' : '否';
    }

    public function getTypeTextAttribute()
    {
        $typeMap = [
            1 => '满减券',    // 满减券
            2 => '折扣券',    // 折扣券
            3 => '代金券',  // 代金券
        ];
        return isset($typeMap[$this->type]) ? $typeMap[$this->type] : '';
    }

    public function getScenesTextAttribute()
    {
        $scenesMap = [
            1 => '线上',    // 线上
            2 => '线下',    // 线下
            3 => '全渠道',    // 全渠道
        ];
        return isset($scenesMap[$this->scenes]) ? $scenesMap[$this->scenes] : '';
    }

    /**
     * *****  time   ****
     */

    public function setStartAtAttribute($value)
    {
        $this->attributes['start_at'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setEndAtAttribute($value)
    {
        $this->attributes['end_at'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setGetStartAttribute($value)
    {
        $this->attributes['get_star'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setGetEndAttribute($value)
    {
        $this->attributes['get_end'] = Carbon::parse($value)->toDateTimeString();
    }

    /**
     * *****  limit_goods   ****
     */

    public function setLimitGoodsAttribute($value)
    {
        foreach ($value as $k => $v) {
            $ids[] = $v['id'];
        }
        $this->attributes['limit_goods'] = implode(',',$ids);
    }

    public function getLimitGoodsAttribute($value)
    {
        if ($value) {
            $ids = explode(',', $value);
            $model = new \ShopEM\Models\Goods();
            return $model->whereIn('id',$ids)->get()->keyBy('id');
        }
    }

    /**
     * *****  limit_shop   ****
     */

    public function setLimitShopAttribute($value)
    {
        foreach ($value as $k => $v) {
            $ids[] = $v['id'];
        }
        $this->attributes['limit_shop'] = implode(',',$ids);
    }

//     public function getLimitShopAttribute($value)
//     {
//         if ($value && $this->shop_id == 0) {
//             return empty($value) ? null : explode(',', $value);
//         }
//         return $value;
//     }

    /**
     * *****  limit_classes   ****
     */

    public function setLimitClassesAttribute($value)
    {
        foreach ($value as $k => $v) {
            $ids[] = $v['id'];
        }
        $this->attributes['limit_classes'] = implode(',',$ids);
    }

//    public function getLimitClassesAttribute($value)
//    {
//        if ($value) {
//            return empty($value) ? null : explode(',', $value);
//        }
//    }

    /**
     * *****  channel   ****
     */

    public function setChannelAttribute($value)
    {
        if ($value !== 'all') {
            $this->attributes['channel'] = implode(',', $value);
        }
    }

    public function getChannelAttribute($value)
    {
        if ($value == 'all') {
            return '全部';
        }else{
            return $value;
        }
    }

    public function getDescAttribute($value)
    {
        if (empty($value)) {
            return '暂无优惠券详情';
        }
        return $value;
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

    /**
     * 优惠卷修改卷名
     *
     * @Author huiho <429294135@qq.com>
     */
    public function editName($params)
    {
        if(empty($params))
        {
            return false;
        }
        try
        {
            $this->where('id',$params['id'])
                  ->where('shop_id',$params['shop_id'])
                  ->update(['name'=>$params['name']]);
            return true;
        }
        catch (\Exception $e)
        {
            //日志
            return false;
        }

    }

    // 追加是否派发属性
    public function getIsDistributeTextAttribute()
    {
        return $this->is_distribute? '是' : '否';
    }


    //检查保存的优惠券信息
    public static function checkSaveCoupon($data)
    {
        $get_star = strtotime($data['get_star']);
        $get_end = strtotime($data['get_end']);

        $start_at = strtotime($data['start_at']);
        $end_at = strtotime($data['end_at']);

        if ($get_end <= $get_star) {
            throw new \Exception("领取结束时间要大于领取开始时间");
        }
        if ($end_at <= $start_at) {
            throw new \Exception("有效结束时间要大于有效开始时间");
        }
        if ($get_end >= $end_at) {
            throw new \Exception("领取结束时间不能大于有效结束时间");
        }
        //满减券
        if ($data['type'] == 1) {
            if ($data['origin_condition'] <= 0 || $data['denominations'] >= $data['origin_condition']) {
                throw new \Exception("满减优惠券规则有误，不支持0元优惠，优惠后至少支付0.01，不可0元");
            }
        }
    }

    public function getActRuleTextAttribute()
    {
        $enabled = [];
        $disabled = [];
        if($this->fullminus_act_enabled == 1) {
            $enabled[] =  '满减活动可用';
        } else {
            $disabled[] =  '满减活动不可用';
        }

        if($this->discount_act_enabled == 1) {
            $enabled[] =  '满折活动可用';
        } else {
            $disabled[] =  '满折活动不可用';
        }

        if($this->group_act_enabled == 1) {
            $enabled[] =  '拼团活动可用';
        } else {
            $disabled[] =  '拼团活动不可用';
        }

        if($this->seckill_act_enabled == 1) {
            $enabled[] =  '秒杀活动可用';
        } else {
            $disabled[] =  '秒杀活动不可用';
        }

        if($this->spread_goods_enabled == 1) {
            $enabled[] =  '推广商品可用';
        } else {
            $disabled[] =  '推广商品不可用';
        }
        $enabled_text = '';
        if (!empty($enabled)) {
            $enabled_text = implode(';', $enabled);
            $enabled_text .= ';';
        }
        $disabled_text = '';
        if (!empty($disabled)) {
            $disabled_text = implode(';', $disabled);
            $disabled_text .= ';';
        }
        return $enabled_text . $disabled_text;
    }

    public function getUsedNumAttribute()
    {
        $num = CouponStockOnline::where('status',2)->where('coupon_id', $this->id)->count();
        return $num;
    }
}
