<?php
/**
 * @Filename        GroupManageUserRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\GroupManageUser;

class GroupManageUserRepository
{
    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterAbles = [];

    /**
     * 前台列表显示字段
     *
     * @var array
     */
    public $listFields = [
        ['dataIndex' => 'id', 'title' => 'ID'],
        ['dataIndex' => 'username', 'title' => '用户名'],
        ['dataIndex' => 'email', 'title' => '邮箱'],
        ['dataIndex' => 'status_text', 'title' => '是否启用'],
        ['dataIndex' => 'is_root_text', 'title' => '是否是超级管理员'],
    ];

    /**
     * 列表搜索
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return mixed
     */
    public function search($request)
    {
        $page_size = empty($request['page_size']) ? config('app.page_size') : $request['page_size'];

        $model = new GroupManageUser();
        $model = filterModel($model, $this->filterAbles, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($page_size);

        return $lists;
    }
}