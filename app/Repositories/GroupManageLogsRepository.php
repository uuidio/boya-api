<?php
/**
 * @Filename        GroupManageLogsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl 2020-3-12
 */

namespace ShopEM\Repositories;

use ShopEM\Models\GroupManageLog;

class GroupManageLogsRepository
{
    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterAbles = [
        'admin_user_id' => ['field' => 'admin_user_id', 'operator' => '='],
        'admin_user_name' => ['field' => 'admin_user_name', 'operator' => '='],
        'created_at'   => ['field' => 'created_at', 'operator' => '='],
    ];

    /**
     * 前台列表显示字段
     *
     * @var array
     */
    public $listFields = [
        ['dataIndex' => 'id', 'title' => 'ID'],
        ['dataIndex' => 'admin_user_name', 'title' => '管理员用户名'],
        ['dataIndex' => 'memo', 'title' => '操作内容'],
        ['dataIndex' => 'status_text', 'title' => '操作结果'],
        ['dataIndex' => 'router', 'title' => '操作路由'],
        ['dataIndex' => 'ip', 'title' => 'ip'],
        ['dataIndex' => 'created_at', 'title' => '操作时间'],
    ];

    /**
     * 列表搜索
     * @Author swl
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $page_size = empty($request['page_size']) ? config('app.page_size') : $request['page_size'];

        $model = new GroupmanageLog();
        $model = filterModel($model, $this->filterAbles, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($page_size);

        return $lists;
    }
}