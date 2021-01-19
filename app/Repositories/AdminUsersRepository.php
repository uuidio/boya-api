<?php

/**
 * AdminUsersRepository.php
 * @Author: nlx
 * @Date:   2020-03-03 16:15:52
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-05-19 14:18:08
 */
namespace ShopEM\Repositories;
use ShopEM\Models\AdminUsers;

class AdminUsersRepository
{
	/*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'username'   => ['field' => 'username', 'operator' => '='],
        'role_id'   => ['field' => 'role_id', 'operator' => '='],
        'is_root'   => ['field' => 'is_root', 'operator' => '='],
        'is_gm'   => ['field' => 'gm_id', 'operator' => '='],
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
            ['dataIndex' => 'id', 'title' => 'id'],
            ['dataIndex' => 'username', 'title' => '平台管理用户名'],
            ['dataIndex' => 'gm_text', 'title' => '归属项目'],
            ['dataIndex' => 'status_text', 'title' => '是否开启'],
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
    public function listItems($request, $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $model = new AdminUsers();
        $model = filterModel($model, $this->filterables, $request);
        
        $lists = $model->orderBy('id', 'asc')->paginate($page_count);

        return $lists;
    }	
}