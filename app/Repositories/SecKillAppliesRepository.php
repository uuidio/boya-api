<?php
/**
 * @Filename SeckillAppliesRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\SecKillApplie;

class SecKillAppliesRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'id' => ['field' => 'id', 'operator' => '='],
        'activity_name' => ['field' => 'activity_name', 'operator' => '='],
        'activity_tag' => ['field' => 'activity_tag', 'operator' => '='],
        'enabled' => ['field' => 'enabled', 'operator' => '='],
        'start_time' => ['field' => 'start_time', 'operator' => '='],
        'end_time' => ['field' => 'end_time', 'operator' => '='],
        'start_time_start' => ['field' => 'start_time', 'operator' => '>='],
        'end_time_end' => ['field' => 'end_time', 'operator' => '<='],
        'created_at' => ['field' => 'created_at', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => '活动id'],
            ['dataIndex' => 'activity_name', 'title' => '活动名称'],
            ['dataIndex' => 'activity_tag', 'title' => '活动标签'],
            ['dataIndex' => 'activity_desc', 'title' => '活动简介'],
            ['dataIndex' => 'apply_begin_time', 'title' => '申请活动开始时间'],
            ['dataIndex' => 'apply_end_time', 'title' => '申请活动结束时间'],
            ['dataIndex' => 'start_time', 'title' => '秒杀开始时间'],
            ['dataIndex' => 'end_time', 'title' => '秒杀结束时间'],
//            ['dataIndex' => 'enroll_limit', 'title' => '店铺报名限制数量'],
//            ['dataIndex' => 'enabled', 'title' => '是否启用'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     *
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 订单查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request)
    {

        $model = new SecKillApplie();
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }

}