<?php

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\LotteryRecord;

class LotteryRecordRepository
{
    /**
     * 定义搜索过滤字段
     * @var array
     */
    protected $filterSearch = [
        'id'              => ['field' => 'id', 'operator' => '='],
        'grant_status'    => ['field' => 'grant_status', 'operator' => '='],
        'user_account_id' => ['field' => 'user_account_id', 'operator' => '='],
        'lottery_name'    => ['field' => 'lottery_name', 'operator' => 'like'],
        'activities_name'    => ['field' => 'activities_name', 'operator' => 'like'],
        'activities_type'    => ['field' => 'activities_type', 'operator' => '='],
        'activities_id'    => ['field' => 'activities_id', 'operator' => '='],
//        'is_show'    => ['field' => 'is_show', 'operator' => '='],
        'gm_id'    => ['field' => 'gm_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'user_account_name', 'title' => '会员名称'],
            ['dataIndex' => 'activities_name', 'title' => '活动名称'],
            ['dataIndex' => 'activities_type_name', 'title' => '活动类型'],
            ['dataIndex' => 'lottery_name', 'title' => '奖项名称'],
            ['dataIndex' => 'number', 'title' => '中奖数量'],
            ['dataIndex' => 'status_name', 'title' => '中奖状态'],
            ['dataIndex' => 'grant_status_name', 'title' => '奖品发放状态'],
            ['dataIndex' => 'grant_time', 'title' => '奖品发放时间'],
            ['dataIndex' => 'integral', 'title' => '使用积分'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
        ];
    }

    /**
     *后台表格列表显示字段
     * @return mixed
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 数据列表
     * @param $request
     * @return mixed
     */
    public function search($request, $downData='')
    {
        $model = new LotteryRecord();
        $model = filterModel($model, $this->filterSearch, $request);


        if ($downData) {
            //下载提供数据
            $lists = $model->orderBy('id', 'desc')->get();
        } else {

            $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);
        }

        return $lists;
    }

    /**
     * 展示列表查询
     *
     * @Author RJie
     * @param $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listShow($request)
    {
        $model = new LotteryRecord();
        $model = $model
            ->where('lottery_records.grant_status',1)
            ->where('lottery_records.gm_id',$request['gm_id'])
            ->where('lottery_records.activities_id',$request['activities_id'])
            ->leftJoin('lotteries as b','lottery_records.lottery_id','b.id')
            ->where('b.is_show',1);



        $lists = $model->orderBy('lottery_records.id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}
