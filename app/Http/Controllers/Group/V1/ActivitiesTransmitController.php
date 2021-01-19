<?php
/**
 * @Filename    ActivitiesTransmitController.php
 *
 * @Copyright   Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License     Licensed <http://www.shopem.cn/licenses/>
 * @authors     hfh
 * @date        2019-03-19 15:16:03
 * @version     V1.0
 */
namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\ActivitiesTransmit;
use ShopEM\Http\Requests\Platform\ActivitiesTransmitRequest;
use ShopEM\Repositories\UserAccountRepository;
use ShopEM\Repositories\ActivitiesTransmitRepository;
use ShopEM\Repositories\ActivitiesTransmitUserlistRepository;
use ShopEM\Repositories\ActivitiesTransmitUserRecommendListRepository;

class ActivitiesTransmitController extends BaseController
{
    /**
     * 传递活动列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesTransmitRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesTransmitList(Request $request, ActivitiesTransmitRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');

        $lists = $repository->Search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 活动添加
     *
     * @Author hfh_wind
     * @param ActivitiesTransmitRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesTransmitCreate(ActivitiesTransmitRequest $request)
    {
        $data = $request->only('name', 'img','note','article_cat_id','is_show','start_time','end_time');

//        $check=ActivitiesTransmit::count();

//        if($check){
//            return $this->resFailed(700, '已有活动,请勿添加');
//        }

        DB::beginTransaction();
        try {
            // 添加活动信息
              ActivitiesTransmit::create($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 活动删除
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesTransmitDelete(Request $request)
    {
        $id=$request['id']??0;
        if (intval($id) <= 0) {
            return $this->resFailed(414,'参数错误!');
        }

        DB::beginTransaction();
        try {
            ActivitiesTransmit::destroy($id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 活动修改
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesTransmitUpdate(ActivitiesTransmitRequest $request)
    {
        $id = $request['id']??0;
        if (intval($id) <= 0) {
            return $this->resFailed(414,'参数错误!');
        }

        $data = $request->only('name', 'img','note','article_cat_id','is_show','start_time','end_time');

        DB::beginTransaction();
        try {
            $info = ActivitiesTransmit::find($id);
            if(empty($info)){
                return $this->resFailed(700,'修改数据不存在!');
            }

            $info->update($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 活动详情
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesTransmitDetail(Request $request)
    {
        $id = $request['id']??0;

        $info = ActivitiesTransmit::find($id);
        if(empty($info)){
            return $this->resFailed(700,'数据不存在!');
        }

        return $this->resSuccess($info);
    }


    /**
     * 参与活动会员列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesTransmitUserlistRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TransmitUserlist(Request $request, ActivitiesTransmitUserlistRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');

        $lists = $repository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }




    /**
     * 参与活动会员列表数据下载
     *
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesTransmitUserlistRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TransmitUserlistDown(Request $request, ActivitiesTransmitUserlistRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');

        $lists = $repository->search($input_data,1);

        //获取下载表头
        $title=$repository->listFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }





    /**
     * 参与活动会员拉新排行
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesTransmitUserRecommendListRepository $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserRecommendList(Request $request, ActivitiesTransmitUserRecommendListRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');

        $lists = $repository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }




    /**
     * 参与活动会员拉新排行信息下载
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesTransmitUserRecommendListRepository $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserRecommendListDown(Request $request, ActivitiesTransmitUserRecommendListRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');

        $lists = $repository->search($input_data,1);
        //获取下载表头
        $title=$repository->listFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }


    /**
     * 会员下级详情
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserRecommendDetai(Request $request,UserAccountRepository $repository)
    {
        $id = $request['id']??0;

        if (intval($id) <= 0) {
            return $this->resFailed(414,'参数错误!');
        }
        $input_data['pid']=$id;
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');
        $lists = $repository->extensionList($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->extensionShowFields(),
        ]);

    }

}
