<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-03-27 17:02:50
 * @version 	V1.0
 */

namespace ShopEM\Repositories;

use Carbon\Carbon;
use ShopEM\Models\SpecialActivity;
use ShopEM\Models\SpecialActivityItem;
use ShopEM\Models\SpecialActivityApply;

class SpecialActivityRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'name' => ['field' => 'name', 'operator' => 'like'],
        'id' => ['field' => 'id', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'name', 'title' => '活动名称'],
            ['dataIndex' => 'type', 'title' => '活动类型'],
            ['dataIndex' => 'apply_time', 'title' => '报名时间'],
            ['dataIndex' => 'use_time', 'title' => '生效时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
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
        $model = new SpecialActivity();
        $model = filterModel($model, $this->filterables, $request);
        if (isset($request['apply_time']['from']) && $request['apply_time']['from']) {
            $model = $model->whereDate('star_apply','>=',$request['apply_time']['from']);
        }
        if (isset($request['apply_time']['to']) && $request['apply_time']['to']) {
            $model = $model->whereDate('end_apply','<=',$request['apply_time']['to']);
        }
        if (isset($request['apply_time']['today']) && $request['apply_time']['today']) {
            $model = $model->whereDate('star_apply','<=',$request['apply_time']['today'])
            			->whereDate('end_apply','>=',$request['apply_time']['today']);
        }
        if (isset($request['use_time']['from']) && $request['use_time']['from']) {
            $model = $model->whereDate('star_time','>=',$request['use_time']['from']);
        }
        if (isset($request['use_time']['to']) && $request['use_time']['to']) {
            $model = $model->whereDate('end_time','<=',$request['use_time']['to']);
        }
        if (isset($request['use_time']['today']) && $request['use_time']['today']) {
            $model = $model->whereDate('star_time','<=',$request['use_time']['today'])
            			->whereDate('end_time','>=',$request['use_time']['today']);
        }
        $lists = $model->orderBy('id', 'desc')->paginate($page_count);
        foreach ($lists as $key => $value) {
            $lists[$key]['apply_time'] = [$value['star_apply'],$value['end_apply']];
            $lists[$key]['use_time'] = [$value['star_time'],$value['end_time']];
        }
        return $lists;
    }
}