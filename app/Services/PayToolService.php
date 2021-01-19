<?php
/**
 * @Filename PayToolService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;


class PayToolService
{

    public function dopay($data, $str_app)
    {

        $str_app = "\\ShopEM\\Services\\Payment\\" . $str_app;

        if (isset($data['payment_id']) && isset($data['pay_app']) && $data['pay_app'] == 'Wxpaymini') 
        {
            $gm_id = \ShopEM\Models\Payment::where('payment_id',$data['payment_id'])->value('gm_id');
//            $gm_id = !empty($appid)? $appid : env('WECHAT_MINI_APPID');
            $pay_app_ins = new $str_app($gm_id);
        }
        else
        {
            $pay_app_ins = new $str_app();
        }

        

        // 支付方式的处理
        $is_payed = true;

        switch ($data['pay_type']) {
//            case "recharge":
//                $sdf['recharge']=true;
//                $is_payed = $pay_app_ins->dopay($sdf);
            case "online":

                $is_payed = $pay_app_ins->dopay($data);
                break;

            case "refund":
//                if ($sdf != 'dorefund') {
//                    throw new \Exception('原支付方式不支持原路返回！请换线下退款方式！');
//                }
                $is_payed = $pay_app_ins->dorefund($data);
                break;
            default:
                $is_payed = false;
                throw new \LogicException('请求支付网关失败！');
                break;
        }

        return $is_payed;
    }


}