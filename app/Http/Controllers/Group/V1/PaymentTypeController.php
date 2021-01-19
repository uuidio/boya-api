<?php
/**
 * @Filename        PaymentTypeController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          zhp
 */

namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Http\Requests\Group\PaymentTypeRequest;
use ShopEM\Models\PanymentType;
use ShopEM\Repositories\PaymentTypeRepository;


class PaymentTypeController extends BaseController
{
    /**
     * 支付类型代码列表
     *
     * @Author zhp
     * @param Request $request,PaymentTypeRepository $paymentTypeRepository
     * @return \Illuminate\Http\JsonResponse
     */
   public function lists(Request $request ,PaymentTypeRepository $paymentTypeRepository){
       $data = $request->all();
       $lists = $paymentTypeRepository->search($data, 1);

       if (empty($lists)) {
           return $this->resFailed(700);
       }
       $type = config('paytype.payment');
       foreach ($lists as $key => $value) {

           $lists[$key]['pay_type'] = $type[$value['pay_type'] ];

       }

       return $this->resSuccess([
           'lists' => $lists,
           'field' => $paymentTypeRepository->listShowFields(),
       ]);
   }
    /**
     * 支付类型     *
     *
     * @Author zhp <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function payType()
    {
        return $this->resSuccess(config('paytype.payment'));
    }
    /**
     * 支付类型代码详情
     *
     * @Author zhp <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {

        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = PanymentType::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }


        return $this->resSuccess($detail);
    }
    /**
     * 添加支付类型代码
     *
     * @Author zhp
     * @param RejectMsgRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
   public function createPayType(PaymentTypeRequest $request){
       $request = $request->only('pay_gm_id','pay_type', 'pay_type_code');

       try {
           $pay = new PanymentType();
           $pay->pay_type = $request['pay_type'];
           $pay->pay_gm_id = $request['pay_gm_id'];
           $pay->pay_type_code = $request['pay_type_code'] ;
           $pay->save();
       } catch (\Exception $e) {
           //日志
           $this->adminlog("支付类型代码添加", 0);
           return $this->resFailed(702, $e->getMessage());
       }

       //日志
       $this->adminlog( "支付类型代码添加", 1);

       return $this->resSuccess();
   }
    /**
     * 更新支付类型代码
     *
     * @Author zhp
     * @param RejectMsgRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePayType(PaymentTypeRequest $request){
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }


        $data = $request->only('pay_gm_id','pay_type', 'pay_type_code');


        $pay = PanymentType::find($id);
        if (empty($pay)) {
            return $this->resFailed(701);
        }
        $msg_text = "更新支付类型代码";
        try {

            PanymentType::delCachePayCode($pay->pay_gm_id,$pay->pay_type);
            $pay->update($data);

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog( $msg_text, 1);
        return $this->resSuccess();
    }
    /**
     * 删除支付类型代码
     *
     * @Author zhp
     * @param RejectMsgRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id){
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $pay = PanymentType::find($id);
        if (empty($pay)) {
            return $this->resFailed(701);
        }

        $msg_text = "删除支付类型代码";
        try {
          
            PanymentType::delCachePayCode($pay->pay_gm_id,$pay->pay_type);
            PanymentType::destroy($id);

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }
}