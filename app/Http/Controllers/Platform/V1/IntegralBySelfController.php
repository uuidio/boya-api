<?php
/**
 * @Filename    IntegralBySelfController.php
 *
 * @Copyright   Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License     Licensed <http://www.shopem.cn/licenses/>
 * @authors     hfh
 * @date        2019-03-19 15:16:03
 * @version     V1.0
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Repositories\IntegralBySelfRepository;
use ShopEM\Models\IntegralBySelf;
use ShopEM\Models\SellerAccount;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Http\Requests\Platform\IntegralBySelfRequest;
use ShopEM\Services\TradeService;
use ShopEM\Services\IntegralBySelfService;
use ShopEM\Jobs\AddEventToCrm;
use ShopEM\Jobs\PointTradePush;


class IntegralBySelfController extends BaseController
{

    /**
     * 展示列表
     *
     * @Author Huiho
     * @param Request $request
     * @param IntegralBySelfRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request , IntegralBySelfRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->Search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);

    }

    /**
     * 积分补录详情
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }


        $detail = IntegralBySelf::find($id);

        if (empty($detail))
        {
            return $this->resFailed(700);
        }

        if ($detail->gm_id != $this->GMID) 
        {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);

    }

    /**
     * 积分补录
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(IntegralBySelfRequest $request)
    {

        $input_data = $request->only('address', 'invoice_at' , 'fee' , 'ticket_id' , 'id' ,'shop_id');
        $id = $input_data['id'] ?? 0;

        if (intval($id) <= 0)
        {
            return $this->resFailed(701,'数据无法修改ID错误');
        }

        $integralBySelfService = new IntegralBySelfService();


        DB::beginTransaction();
        try
        {
            $qualified_data =  $integralBySelfService->_checkData($input_data);

            $info = IntegralBySelf::find($id);
            //$gm_id = $this->GMID;

            if(empty($info))
            {
                return $this->resFailed(700,'修改数据不存在!');
            }
            //推送给crm
            $point = $this->crmTradeJob($qualified_data,$info);

            if($point !== false)
            {
                $update_data = [
                    'ticket_id'   => $qualified_data['ticket_id'],
                    'fee'         => $qualified_data['fee'],
                    'invoice_at'  => $qualified_data['invoice_at'],
                    'address'     => $qualified_data['address']??'',
                    'shop_id'     => $qualified_data['shop_id'] ?? 0,
                    'status'      => 'success',
                ];
                $info->update($update_data);
            }
            else
            {
                return $this->resFailed(702 , '提交失败');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }
        $return['next_id'] = $this->nextIntegral($id);
        return $this->resSuccess($return,'提交成功');

    }
    /**
     * [nextIntegral 下一条需要处理的id]
     * @param  [type] $current_id [当前处理的]
     * @return [type]             [description]
     */
    private function nextIntegral($current_id)
    {
        $id = IntegralBySelf::where('gm_id',$this->GMID)
            ->where('id','>',$current_id)
            ->where('status','ready')
            ->value('id');
        return empty($id)?0:$id;
    }


    public function crmTradeJob($submit,$info)
    {
        try {
            $tradeService = new TradeService();
            //记录crm积分推送，目前使用的是crm的比例 10:1
            $pointLog = array(
                'gm_id'     => $this->GMID,
                'amount'    => $submit['fee'],
                'user_id'   => $info->user_id,
                'behavior'  => "自助积分" ,
                'remark'    => '自助积分,小票号：'. $submit['ticket_id'],
                'type'      => 'obtain',
            );
            $log_id = $tradeService->crmPushPoint($pointLog);

            //推送crm记录状态
            $push['push_crm'] = 1;
            DB::table('integral_by_selves')->where('id',$info->id)->update($push);

            //异步推送订单信息给CRM
            $store_code = \ShopEM\Models\CrmMasterStore::where('id',$submit['shop_id'])->value('storeCode');
            $cardType = UserRelYitianInfo::where('user_id',$pointLog['user_id'])->where('gm_id',$pointLog['gm_id'])->value('card_code');
            $jobInfo = [
                'storeCode'        => $store_code,                         //门店编码
                'transTime'        => $info->created_at,
                'cardCode'         => $cardType,
                'receiptNo'        => $submit['ticket_id'],
                'payableAmount'    => $submit['fee'],
                'netAmount'        => $submit['fee'],
                'discountAmount'   => 0,
                'getPointAmount'   => $submit['fee'],
                'log_id'           => $log_id,
                'integral_id'      => $info->id,
                'log_type'         => 'selfIncr', 
            ];
            PointTradePush::dispatch($jobInfo);
        }
        catch (\Exception $e)
        {
            throw new \LogicException($e->getMessage());
        }
        return true;
    }


    /**
     * 直接驳回
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function examine(Request $request)
    {
        $input_data = $request->only('id','reject_reason');

        $id = intval($input_data['id']) ?? 0;
        if ($id <= 0)
        {
            return $this->resFailed(701,'数据无法修改ID错误');
        }

        if(empty($input_data['reject_reason']))
        {
            return $this->resFailed( 702, '驳回需要填写原因');
        }

        DB::beginTransaction();
        try
        {
            $info = IntegralBySelf::find($id);
            if(empty($info))
            {
                return $this->resFailed(700,'修改数据不存在!');
            }
            $update_data = [
                'status' => 'reject',
                'reject_reason' => $input_data['reject_reason'],
            ];
            $info->update($update_data);

            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();

    }


}
