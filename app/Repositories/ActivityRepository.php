<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-03-14 10:09:04
 * @version 	V1.0
 */

namespace ShopEM\Repositories;

use Carbon\Carbon;
use ShopEM\Models\Activity;

class ActivityRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'channel' => ['field' => 'channel', 'operator' => '='],
        'name' => ['field' => 'name', 'operator' => '='],
        'status'   => ['field' => 'status', 'operator' => '='],
        'type'   => ['field' => 'type', 'operator' => '='],
        'star_time'   => ['field' => 'start_at', 'operator' => '='],
        'end_time'   => ['field' => 'end_at', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields($is_show='')
    {
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称','hide'=>isshow_models($is_show,['platform'])],
            ['dataIndex' => 'name', 'title' => '活动名称'],
            ['dataIndex' => 'channel', 'title' => '适用平台'],
            ['dataIndex' => 'type_text', 'title' => '活动类型'],
            ['dataIndex' => 'status_text', 'title' => '状态'],
            ['dataIndex' => 'user_type', 'title' => '使用者范围'],
            ['dataIndex' => 'star_time', 'title' => '生效时间'],
            ['dataIndex' => 'end_time', 'title' => '失效时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 获取列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param int $page_count
     * @return mixed
     */
    public function listItems($request, $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $couponModel = new Activity();
        $couponModel = filterModel($couponModel, $this->filterables, $request);
        if (isset($request['user_type']) && $request['user_type']) {
            $couponModel = $couponModel->where(function ($query) use ($request)  {
                $query->where('user_type', '=', 'all')
                      ->orWhere('user_type', 'like', '%'.$request['user_type'])
                      ->orWhere('user_type', 'like', '%'.$request['user_type'].'%')
                      ->orWhere('user_type', 'like', $request['user_type'].'%');
            });
        }
        $lists = $couponModel->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }
}