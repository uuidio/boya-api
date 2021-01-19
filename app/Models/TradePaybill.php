<?php
/**
 * @Filename        TradePaybill.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TradePaybill extends Model
{
    protected $guarded = [];

    protected $appends = ['status_text', 'payed_at', 'pay_app', 'gm_name' , 'shop_name','trade_no','pay_app_name'];

    // field status 状态
    const SUCC = 'succ';
    const FAILED = 'failed';
    const CANCEL = 'cancel';
    const ERROR = 'error';
    const INVALID = 'invalid';
    const PROGRESS = 'progress';
    const TIMEOUT = 'timeout';
    const READY = 'ready';
    const PAYING = 'paying';

    public static $statusMap = [
        self::SUCC           => '支付成功',    // 支付成功
        self::FAILED           => '支付失败',    // 支付失败
        self::CANCEL           => '未支付',    // 未支付
        self::ERROR           => '处理异常',    // 处理异常
        self::INVALID           => '非法参数',    // 非法参数
        self::PROGRESS           => '已付款至担保方',    // 已付款至担保方
        self::TIMEOUT           => '超时',    // 超时
        self::READY           => '准备中',    // 准备中
        self::PAYING           => '支付中',    // 支付中
    ];

    public function getStatusTextAttribute()
    {
        return self::$statusMap[$this->status];
    }

    public function getPayedAtAttribute()
    {
        return $this->payed_time ? $this->payed_time : '--';
    }

    public function getPayAppAttribute()
    {
        $payment = Payment::where('payment_id', $this->payment_id)->first();
        return isset($payment['pay_app']) ? $payment['pay_app'] : '--';
    }


    public function getPayAppNameAttribute()
    {
        return Payment::$payAppMap[$this->pay_app]??'';
    }

    public function getTradeNoAttribute()
    {
        $payment = Payment::where('payment_id', $this->payment_id)->first();
        return isset($payment['trade_no']) ? $payment['trade_no'] : '--';
    }

    // 追加所属项目名称
    public function getGmNameAttribute(){
        $gm = GmPlatform::find($this->gm_id);
        return $gm['platform_name']??'';
    }

    //追加店铺名
    public function getShopNameAttribute()
    {
        $shop_id = DB::table('trades')->where('tid',$this->tid)->value('shop_id');
        $shop_name = DB::table('shops')->where('id',$shop_id)->value('shop_name');
        return $shop_name ?? '';
    }

}
