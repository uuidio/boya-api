<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserPointLog extends Model
{
    protected $guarded = [];

    public static $logTypeMap = [
        'normal' 	=> '正常类型', // 签到/注册
        'exchange' 	=> '兑换类型', // 使用/兑换积分
        'trade'		=> '订单类型'  // 订单赠送/扣减
    ];
}
