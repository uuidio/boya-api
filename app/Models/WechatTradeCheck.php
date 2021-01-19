<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WechatTradeCheck extends Model
{
    //
    protected $guarded = [];
    protected $appends = [
        'gm_name',
        'shop_name',
        'status_text',
        'deal_status_text',
        'trade_type_text',
        'abnormal_reason_text',
    ];

    //集团
    public function getGmNameAttribute()
    {
       $platform_name = DB::table('gm_platforms')->where('gm_id', $this->gm_id)->value('platform_name');
       return $result = $platform_name ?? '--';
    }

    //店铺
    public function getShopNameAttribute()
    {
        $shop_name = DB::table('shops')->where('id', $this->shop_id)->value('shop_name');
        return $result = $shop_name ?? '--';
    }

    //对账状态
    public function getStatusTextAttribute()
    {
        $statusMap =
        [
            '0' => '未对账',
            '1' => '微信对账成功',
            '2' => '微信对账失败',
            '3' => '返款对账成功',
            '4' => '返款对账失败',
        ];
        return $statusMap[$this->status] ??  '---';
    }

    //处理状态
    public function getDealStatusTextAttribute()
    {
        $statusMap =
        [
            '0' => '待对账',
            '1' => '待返款',
            '2' => '可返款',
            '3' => '已处理',
            '4' => '不可返款',
            '5' => '已返款',
        ];
        return $statusMap[$this->deal_status] ??  '---';
    }


    //交易状态
    public function getTradeTypeTextAttribute()
    {
        $statusMap =
            [
                'TRADE' => '交易',
                'REFUND' => '退款',
            ];
        return $statusMap[$this->trade_type] ??  '---';
    }

    //异常原因
    public function getAbnormalReasonTextAttribute()
    {
        $statusMap =
            [
                'REPEAT' => '重复',
                'EMPTY' => '数据为空',
                'MISMATCH' => '数据不匹配',
                'NOT_TRADE' => '无交易订单',
                'MISMATCH_AMOUNT' => '金额不匹配',
            ];
        return $statusMap[$this->abnormal_reason] ??  '---';

    }

}
