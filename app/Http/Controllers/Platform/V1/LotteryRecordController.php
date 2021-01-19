<?php

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\UserAccount;
use ShopEM\Repositories\LotteryRecordRepository;

class LotteryRecordController extends BaseController
{
    /**
     * 中奖记录列表
     *
     * @Author RJie
     * @param Request $request
     * @param LotteryRecordRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request,LotteryRecordRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');

        // 手机号搜索
        if($mobile = $request->input('mobile')){
            $user = UserAccount::where('mobile',$mobile)->first();
            if($user){
                $input_data['user_account_id'] = $user->id;
            }else{
                $input_data['user_account_id'] = 0;
            }
        }

        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 中奖记录下载
     * @Author hfh_wind
     * @param Request $request
     * @param LotteryRecordRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ListDown(Request $request,LotteryRecordRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');

        // 手机号搜索
        if($mobile = $request->input('mobile')){
            $user = UserAccount::where('mobile',$mobile)->first();
            if($user){
                $input_data['user_account_id'] = $user->id;
            }else{
                $input_data['user_account_id'] = 0;
            }
        }

        $lists = $repository->search($input_data,1);

        //获取下载表头
        $title=$repository->listFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }




}
