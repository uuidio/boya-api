<?php
/**
 * @Filename        GroupPermissionMenuRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Http\Request;
use ShopEM\Models\GroupPermissionMenu;

class GroupPermissionMenuRepository
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
        ['dataIndex' => 'title', 'title' => '菜单名称'],
        ['dataIndex' => 'route_path', 'title' => '路由地址'],
        ['dataIndex' => 'frontend_route_path', 'title' => '前端路由地址'],
        ['dataIndex' => 'hide', 'title' => '显示'],
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
        $page_size = empty($request['page_size']) ? config('app.per_page') : $request['page_size'];

        $model = new GroupPermissionMenu();
        $model = filterModel($model, $this->filterAbles, $request);
        $lists = $model->orderBy('listorder', 'desc')->orderBy('id', 'asc')->paginate($page_size);

        return $lists;
    }

    /**
     * 获取所有菜单
     *
     * @Author moocde <mo@mocode.cn>
     * @param string $auth_provider
     * @return mixed
     */
    public function getAll($auth_provider = 'group_users')
    {
        return GroupPermissionMenu::where('auth_provider', $auth_provider)
            ->orderBy('listorder', 'desc')
            ->orderBy('id', 'asc')
            ->get();
    }
}