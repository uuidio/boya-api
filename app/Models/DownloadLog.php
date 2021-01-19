<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadLog extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['type_text','status_text'];


    /**
     * 追加类型
     * @Author hfh_wind
     * @return string
     */
    public function getTypeTextAttribute()
    {
        $type = [
            'TradeOrder'    => '订单',
            'UserAccount'   => '会员',
            'UserCowCoinLog'=>'积分转牛币',
            'TradePayment'  => '支付订单',
            'PromoterLists' => '佣金管理',          
            'GoodsCost'     => '成本结算',
            'Goods'         => '商品列表',
        ];
        return $type[$this->type] ?? '--';
    }

    /**
     * 追加状态
     * @Author hfh_wind
     * @return string
     */
    public function getStatusTextAttribute()
    {
        $type = [
            '0' => '导出中',
            '1' => '已完成',
            '2' => '失败',
        ];
        return $type[$this->status] ?? '--';
    }
}
