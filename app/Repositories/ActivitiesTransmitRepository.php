<?php
/**
 * @Filename ActivitiesTransmitRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\ActivitiesTransmit;

class ActivitiesTransmitRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'      => ['field' => 'id', 'operator' => '='],
        'name'    => ['field' => 'name', 'operator' => 'like'],
        'is_show' => ['field' => 'is_show', 'operator' => '='],
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
            ['dataIndex' => 'name', 'title' => '活动名称'],
            ['dataIndex' => 'img', 'title' => '活动图片'],
            ['dataIndex' => 'article_cat_text', 'title' => '关联文章'],
            ['dataIndex' => 'is_show_text', 'title' => '是否开启'],
            ['dataIndex' => 'start_time', 'title' => '活动开始时间'],
            ['dataIndex' => 'end_time', 'title' => '活动结束时间'],
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
        $model = new ActivitiesTransmit();
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }

}