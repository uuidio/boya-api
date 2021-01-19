<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeAftersaleLog extends Model
{
    protected $guarded = [];
    protected $appends = ['aftersales_type_text', 'status_text', 'progress_text'];
    protected $aftersalesTypeSign = ['ONLY_REFUND','REFUND_GOODS','EXCHANGING_GOODS'];
    protected $aftersalesTypeText = [
        'ONLY_REFUND'       =>  '仅退款',
        'REFUND_GOODS'      =>  '退货退款',
        'EXCHANGING_GOODS'  =>  '换货',
    ];
    protected $statusText = ['待处理','处理中','已处理','已驳回'];
    protected $progressText = [
        0   =>  '等待商家处理',
        1   =>  '商家接受申请等待消费者回寄',
        2   =>  '消费者回寄，等待商家收货确认',
        3   =>  '商家已驳回',
        4   =>  '商家已处理',
        5   =>  '商家确认收货',
        6   =>  '平台驳回退款申请',
        7   =>  '平台已处理退款申请',
        8   =>  '同意退款提交到平台等待平台处理',
        9   =>  '会员取消',
    ];

    public function getAftersalesTypeAttribute($value)
    {
        return $this->aftersalesTypeSign[$value];
    }

    public function getAftersalesTypeTextAttribute()
    {
        return $this->aftersalesTypeText[$this->aftersales_type];
    }

    public function getStatusTextAttribute()
    {
        return $this->statusText[$this->status];
    }

    public function getProgressTextAttribute()
    {
        return $this->progressText[$this->progress];
    }

    public function setAftersalesTypeAttribute($value)
    {
        $statusSign = array_flip($this->aftersalesTypeSign);
        $this->attributes['aftersales_type'] = $statusSign[$value];
    }
}
