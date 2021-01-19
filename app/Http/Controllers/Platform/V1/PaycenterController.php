<?php
/**
 * @Filename        PaymentController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\PaymentCfgs;
use ShopEM\Services\Payment\Wxpayjsapi;
use ShopEM\Services\Payment\Deposit;

class PaycenterController extends BaseController
{

    public function getPaymentSetting(Request $request)
    {
        $source_data = $request->pay_type;
        //wxpayApp , wxpayjsapi , wxqrpay , alipay , malipay , alipayApp , deposit
        if (!$source_data) {
            throw new \Exception('请传入支付类型pay_type');
        }
        $setting=$this->get_config($source_data['pay_type']);

        return $setting;
    }



    public function editPayment(Request $request)
    {
        $source_data = $request->all();

        if(!$source_data['pay_type']){
            throw new \Exception('请传入支付类型');
        }

        $relation=$this->get_config($source_data['pay_type']);

        $return_data=$this->return_data($source_data,$relation);

        $save_cfgs['name']=$source_data['pay_name'];
        $save_cfgs['pay_type']=$source_data['pay_type'];
        $save_cfgs['describe']=isset($source_data['describe'])?$source_data['describe']:"";
        $save_cfgs['configure']=json_encode($return_data);
        $save_cfgs['orderby']=isset($source_data['orderby'])?$source_data['orderby']:0;
        $save_cfgs['on_use']=isset($source_data['on_use'])?$source_data['on_use']:0;
        $save_cfgs['default']=isset($source_data['default'])?$source_data['default']:0;

        $check=PaymentCfgs::where(['pay_type'=>$source_data['pay_type']])->count();

        if($check){
            PaymentCfgs::where(['pay_type'=>$source_data['pay_type']])->update($save_cfgs);
        }else{
            PaymentCfgs::create($save_cfgs);
        }

        return $this->resSuccess([], '保存成功!');
    }


    /**
     *  配置字段转化
     *
     * @Author hfh_wind
     * @param $source_data
     * @param $relation
     * @return array
     */
    public function return_data($source_data, $relation)
    {
        $data = [];
        foreach ($relation as $k => $v) {
            $data[$v] = isset($source_data[$k])?$source_data[$k]:0;
        }
        return $data;
    }


    public function get_config($pay_name)
    {
        switch($pay_name){
            case 'Wxpayjsapi':
                //微信H5支付
                $data = Wxpayjsapi::setting();
                break;
            case 'WxpayApp':
                //微信app支付
                $data = WxpayApp::setting();
                break;
            case 'Wxqrpay':
                //微信二维码支付
                $data = Wxqrpay::setting();
                break;
            case 'Wxpaywap':
                //浏览器来唤起微信支付
                $data = Wxpaywap::setting();
                break;
                
            case 'Alipay':
                //支付宝支付
                $data = Alipay::setting();
                break;
            case 'Malipay':
                //支付宝H5支付
                $data = Malipay::setting();
                break;
            case 'AlipayApp':
                //支付宝app支付
                $data = AlipayApp::setting();
                break;
            case 'Deposit':
                //余额支付
                $data = Deposit::setting();
                break;
        }

        return $data;
    }


    /**
     * 更新支付方式
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePayApp(Request $request)
    {
        testLog($request->all());
        Payment::where('payment_id', $request->payment_id)
            ->where('user_id', $this->user['id'])
            ->update(['pay_app' => $request->pay_app]);

        return $this->resSuccess();
    }

}