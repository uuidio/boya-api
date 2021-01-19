<?php
/**
 * @Filename        Payment.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';
    public $incrementing = false;
    protected $fillable = ['payment_id', 'amount', 'status', 'user_id', 'pay_app', 'consume_point_fee', 'points_fee', 'platform_coupon_fee'];

    // field status 支付状态
    const SUCC = 'succ';
    const FAILED = 'failed';
    const CANCEL = 'cancel';
    const ERROR = 'error';
    const INVALID = 'invalid';
    const PROGRESS = 'progress';
    const TIMEOUT = 'timeout';
    const READY = 'ready';
    const PAYING = 'paying';

    public static $payStatusMap = [
        self::SUCC     => '支付成功',
        self::FAILED   => '支付失败',
        self::CANCEL   => '未支付',
        self::ERROR    => '处理异常',
        self::INVALID  => '非法参数',
        self::PROGRESS => '已付款至担保方',
        self::TIMEOUT  => '超时',
        self::READY    => '准备中',
        self::PAYING   => '支付中',
    ];

    // field pay_type 支付类型
    const ONLINE = 'online';
    const OFFLINE = 'offline';

    public static $payTypeMap = [
        self::ONLINE  => '在线支付',
        self::OFFLINE => '货到付款',
    ];

    // field pay_app 支付方式名称
    const Wxpaywap = 'Wxpaywap';
    const Wxpayjsapi = 'Wxpayjsapi';
    const WxpayApp = 'WxpayApp';
    const Wxqrpay = 'Wxqrpay';
    const Alipay = 'Alipay';
    const Malipay = 'Malipay';
    const AlipayApp = 'AlipayApp';
    const Deposit = 'Deposit';
    const Wxpaymini = 'Wxpaymini';
    const Zero = 'Zero';
    const WalletPhysical = 'WalletPhysical';
    const WalletVirtual = 'WalletVirtual';

    // wxpayApp , wxpayjsapi , wxqrpay , alipay , malipay , alipayApp , deposit
    public static $payAppMap = [
        self::Wxpaywap   => '微信外H5支付',
        self::Wxpayjsapi => 'H5微信支付',
        self::WxpayApp   => 'app微信支付',
        self::Wxqrpay    => '微信二维码支付',
        self::Alipay     => '支付宝支付',
        self::Malipay    => '手机支付宝',
        self::AlipayApp  => 'app支付宝',
        self::Deposit    => '预存款',
        self::Wxpaymini  => '微信小程序支付',
        self::Zero       => '0元支付',
        self::WalletPhysical => '实体卡支付',
        self::WalletVirtual => '电子钱包余额支付',
    ];


}
