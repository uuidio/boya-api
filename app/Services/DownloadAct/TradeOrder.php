<?php

/**
 * TradeOrder.php
 * @Author: nlx
 * @Date:   2020-07-06 15:23:43
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-07-07 09:59:53
 */
namespace ShopEM\Services\DownloadAct;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Trade;

class TradeOrder extends Common
{
	protected $tableName = '订单列表';

	public function getFilePath()
	{
        $path = $this->tableName ."_" . date('Y-m-d_H_i_s') . '.'. $this->suffix;
		return $path;
	}

	public function downloadJob($data)
	{
		$id = $data['log_id'];
        $info = DB::table('download_logs')->where('id' , $id)->select('desc','gm_id','shop_id')->first();

        if(isset($info->desc) && !empty($info->desc))
        {
            $log_info = json_decode($info->desc);
            $log_info = (array)$log_info;
        }
        else
        {
            throw new \Exception('参数有误');
        }

        //过滤筛选条件
        $filterables = [
            'shop_id' => ['field' => 'shop_id', 'operator' => '='],
            'user_id' => ['field' => 'user_id', 'operator' => '='],
            'cancel_status' => ['field' => 'cancel_status', 'operator' => '='],
            'status'   => ['field' => 'status', 'operator' => '='],
            'pick_type'   => ['field' => 'pick_type', 'operator' => '='],
            'gm_id'   => ['field' => 'gm_id', 'operator' => '='],
        ];
        $model = new Trade();
        $model = filterModel($model, $filterables, $log_info);

        if (isset($log_info['time']))
        {
            $from = $log_info['time']->from;
            $to = $log_info['time']->to;
            switch ($log_info['time']->type)
            {
                case 'pay_time':
                    $model = $model->where('pay_time','>=',$from)->where('pay_time','<=',$to);
                    break;
                case 'end_time':
                    $model = $model->where('end_time','>=',$from)->where('end_time','<=',$to)->where('status', 'TRADE_FINISHED');
                    break;
                case 'created_at':
                    $model = $model->where('created_at','>=',$from)->where('created_at','<=',$to);
                    break;
            }
        }
        $res = $model->get();

        $order_list = [];
        /*
         *组装导出表结构
         */
        foreach ($res as $key => &$value)
        {
            $value->shop_name = $value->shop_info->shop_name;
            $user_info = \ShopEM\Models\UserAccount::find($value->user_id);
            $value->login_account = isset($user_info->login_account)?$user_info->login_account:'数据丢失';
            $value->trade_mobile = isset($user_info->mobile)?$user_info->mobile:'数据丢失';
            $value->receiver_addr_info = $value->receiver_province.$value->receiver_city.$value->receiver_county.$value->receiver_address;
            $value->pick_statue_text = '';
            if ($value->pick_type == 1) {
                $value->pick_statue_text = ($value->pick_statue == 1) ? '已提货' : '未提货';
            }
            $trade_order = $value->trade_order->toArray();
            foreach ($trade_order as $order) {
                $order['payment_id'] = $value->payment_id;
                $order['shop_name'] = $value->shop_name;
                $order['login_account'] = $value->login_account;
                $order['trade_mobile'] = $value->trade_mobile;
                $order['status_text'] = $value->status_text;
                $order['cancel_text'] = $value->cancel_text;
                $order['cancel_reason'] = $value->cancel_reason;
                $order['created_at'] = \Carbon\Carbon::parse($value->created_at)->toDateTimeString();
                $order['pay_type_text'] = $value->pay_type_text;
                $order['pay_time'] = $value->pay_time;
                $order['consign_time'] = $value->consign_time;
                $order['trade_amount'] = $value->amount_text;
                $order['points_fee'] = $value->points_fee;
                $order['trade_total_fee'] = $value->total_fee;
                $order['post_fee'] = $value->post_fee;
                $order['discount_fee'] = $value->discount_fee;
                $order['platform_discount'] = $value->platform_discount;
                $order['seller_coupon_discount'] = $value->seller_coupon_discount;
                $order['seller_discount'] = $value->seller_discount;
                $order['obtain_point_fee'] = $value->obtain_point_fee;
                $order['consume_point_fee'] = $value->consume_point_fee;
                $order['receiver_name'] = $value->receiver_name;
                $order['receiver_tel'] = $value->receiver_tel;
                $order['receiver_addr_info'] = $value->receiver_addr_info;
                $order['buyer_message'] = $value->buyer_message;
                $order['shop_memo'] = $value->shop_memo;
                $order['pick_type_name'] = $value->pick_type_name;
                $order['shipping_type'] = $value->shipping_type;
                $order['invoice_no'] = $value->invoice_no;
                $order['pick_code'] = $value->pick_code;
                $order['pick_statue_text'] = $value->pick_statue_text;
                $order['ziti_addr'] = $value->ziti_addr;
                $order['confirm_at'] = $value->confirm_at ?? '--';
                //$order['end_time'] = $value->end_time ?? '--';
                $order['activity_sign_text'] = $value->activity_sign_text ?? '线上交易';
                $order_list[] = $order;
            }
        }

        $export_title = ['商户订单号','订单号','子订单号','商品名称','SKU信息','商品货号','商品价格','商品市场价','商品成本价','购买数量','子订单实付金额','应付金额','优惠分摊','积分抵扣的金额','退款金额','所属店铺','用户账号','用户手机','订单状态','取消状态','取消原因','发货时间','下单时间','支付方式','支付时间','总实付金额','积分抵消','总价','邮费','优惠金额',    '平台优惠卷金额', '商家优惠卷金额', '店铺促销金额','买家获得积分','买家消耗积分','收货人姓名','收货人电话','收货人地址','买家留言','卖家备注','提货方式','快递方式','快递单号','提货码','提货状态','自提地址','用户确认收货时间/商品核销时间',
            //'订单完成时间',
            '活动渠道'];
        $filterVal = ['payment_id','tid','oid','goods_name','sku_info','goods_serial','goods_price','goods_marketprice','goods_cost','quantity','amount_text','total_fee','avg_discount_fee','avg_points_fee','refund_fee_text','shop_name','login_account','trade_mobile','status_text','cancel_text','cancel_reason','consign_time','created_at','pay_type_text','pay_time','trade_amount','points_fee','trade_total_fee','post_fee','discount_fee','platform_discount', 'seller_coupon_discount', 'seller_discount','obtain_point_fee','consume_point_fee','receiver_name','receiver_tel','receiver_addr_info','buyer_message','shop_memo','pick_type_name','shipping_type','invoice_no','pick_code','pick_statue_text','ziti_addr','confirm_at',//'end_time',
            'activity_sign_text'];

        // 集团端的导出需要加上所属项目
        if($info->shop_id==0&&$info->gm_id==0){
            array_unshift($export_title,'所属项目');
            array_unshift($filterVal,'gm_name');
        }
        $exportData = []; //声明导出数据
        try
        {

            // 提取导出数据
            foreach ($order_list as $k => $v)
            {
                foreach ($filterVal as $fv)
                {
                    if ($fv === 0) {
                        $exportData[$k][$fv] = '0';
                    } else {
                        $vfv = $this->filterEmoji(urldecode($v[$fv]));
                        $vfv = $this->startWith($vfv, "=") ? str_replace("=", "-", $vfv) : $vfv;
                        $exportData[$k][$fv] = $vfv ? $vfv : '';
                    }
                }
            }

            array_unshift($exportData, $export_title); // 表头数据合并

        }
        catch (\Exception $e)
        {
            $message = $e->getMessage();
            throw new \Exception($message);
        }
        return $exportData;
	}
}