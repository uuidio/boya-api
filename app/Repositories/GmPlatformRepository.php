<?php

/**
 * GmPlatformRepository.php
 * @Author: nlx
 * @Date:   2020-03-03 14:49:14
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-05-19 14:19:22
 */
namespace ShopEM\Repositories;
use ShopEM\Models\GmPlatform;

class GmPlatformRepository
{
	
	/*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'platform_name' => ['field' => 'platform_name', 'operator' => '='],
        'admin_username'   => ['field' => 'admin_username', 'operator' => '='],
        'open_point_exchange'   => ['field' => 'open_point_exchange', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
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
            ['dataIndex' => 'gm_id', 'title' => 'GM_ID'],
            ['dataIndex' => 'platform_name', 'title' => '平台项目名称'],
            ['dataIndex' => 'type_name', 'title' => '项目类型'],
            ['dataIndex' => 'platform_no', 'title' => '项目编号'],
            ['dataIndex' => 'platform_id', 'title' => '项目ID'],
            ['dataIndex' => 'listorder', 'title' => '权重'],
            ['dataIndex' => 'admin_username', 'title' => '超级管理员用户名'],
            ['dataIndex' => 'updated_at', 'title' => '更新时间'],
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
    //public function listItems($request=[], $page_count = 0)
    public function listItems($request)
    {
        //$page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $model = new GmPlatform();

        $orderby = 'gm_id';
        $direction = 'desc';
        if (isset($request['orderby']) ) 
        {
            $orderby = $request['orderby'];
            unset($request['orderby']);
        }
        if (isset($request['direction']) && in_array($request['direction'], ['desc', 'asc'])) 
        {
            $direction = $request['direction'];
            unset($request['direction']);
        }

        $model = filterModel($model, $this->filterables, $request);
        $lists = $model->orderBy($orderby, $direction)->paginate($request['per_page']);

        return $lists;
    }

    /**
     * [normalLists 获取普通项目列表]
     * @param  array  $request [description]
     * @return [type]          [description]
     */
    public function normalLists($request=[])
    {
        
        $model = new GmPlatform();
        $orderby = 'listorder';
        $direction = 'desc';
        if (isset($request['orderby']) ) 
        {
            $orderby = $request['orderby'];
            unset($request['orderby']);
        }
        if (isset($request['direction']) && in_array($request['direction'], ['desc', 'asc'])) 
        {
            $direction = $request['direction'];
            unset($request['direction']);
        }
        $model = filterModel($model, $this->filterables, $request);

        $types[] = 'normal';
        if (isset($request['use_self']) && $request['use_self']>0) {
            $types[] = 'self';
        }
        $model = $model->where('status','=',1)->whereIn('type',$types);
        $lists = $model->select('gm_id','platform_name','address','use_obtain_point','longitude','latitude')->orderBy($orderby, $direction)->get();
        return $lists;
    }
}