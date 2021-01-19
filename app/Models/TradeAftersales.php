<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TradeAftersales extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['progress_text', 'aftersales_type_text', 'status_text', 'shop_name', 'user_name','gm_name' ,'order_date_at','refund_at'];

    public function getProgressTextAttribute()
    {
        switch ($this->progress) {
            case '0':
                return '等待商家处理';
                break;
            case '1':
                return '商家接受申请等待消费者回寄';
                break;
            case '2':
                return '消费者回寄，等待商家收货确认';
                break;
            case '3':
                return '商家已驳回';
                break;
            case '4':
                return '商家已处理';
                break;
            case '5':
                return '商家确认收货';
                break;
            case '6':
                return '平台驳回退款申请';
                break;
            case '7':
                return '平台已处理退款申请';
                break;
            case '8':
                return '同意退款提交到平台等待平台处理';
                break;
            default:
                return '';
        }
    }

    public function getAftersalesTypeTextAttribute()
    {
        switch ($this->aftersales_type) {
            case 'ONLY_REFUND':
                return '仅退款';
                break;
            case 'REFUND_GOODS':
                return '退货退款';
                break;
            case 'EXCHANGING_GOODS':
                return '换货';
                break;
            default:
                return '';
        }
    }

    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case '0':
                return '待处理';
                break;
            case '1':
                return '处理中';
                break;
            case '2':
                return '已处理';
                break;
            case '3':
                return '已驳回';
                break;
            default:
                return '';
        }
    }

    public function getEvidencePicAttribute($value)
    {
        return empty($value) ? null : explode(',', $value);
    }

    /**
     * 店铺信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getShopNameAttribute()
    {
        $shop = Shop::select('shop_name')->where('id', $this->shop_id)->first();
        return $shop['shop_name'] ?? '';
    }

    public function getUserNameAttribute()
    {
        $user = UserAccount::select('mobile')->where('id', $this->user_id)->first();
        return $user->mobile ?? '匿名';
    }

    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getGmNameAttribute()
    {
        $shop_info = GmPlatform::where('gm_id', '=', $this->gm_id)->select('platform_name')->first();

        return isset($shop_info['platform_name']) ? $shop_info['platform_name'] : '';
    }


    /**
     * 追加下单日期
     * @Author Huiho
     * @return string
     */
    public function getOrderDateAtAttribute()
    {
        $time = DB::table('trade_orders')->where('oid', $this->attributes['oid'])->value('created_at');
        return isset($time) ? $time : '--';
    }


    /**
     * 追加退款实付金额
     * @Author Huiho
     * @return string
     */
    public function getRefundAtAttribute()
    {
        //售后退款(多子订单)
        $refund_info = DB::table('trade_refunds')->where('oid', $this->attributes['oid'])->where('status', '=','1')->select('updated_at')->first();

        if($refund_info)
        {
            return $refund_info->updated_at;
        }
        else
        {
            return '--';
        }

    }
}
