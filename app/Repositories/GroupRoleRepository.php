<?php
/**
 * @Filename        GroupRoleRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl 2020-3-12
 */

namespace ShopEM\Repositories;

use ShopEM\Models\GroupRole;

class GroupRoleRepository
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
        ['dataIndex' => 'name', 'title' => '角色名称'],
        ['dataIndex' => 'status_text', 'title' => '是否启用'],
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

        $model = new GroupRole();
        $model = filterModel($model, $this->filterAbles, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($page_size);

        return $lists;
    }
}