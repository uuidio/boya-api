<?php
/**
 * @Filename        PermissionMenuControllerController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\PermissionMenuRequest;
use ShopEM\Models\PermissionMenu;
use ShopEM\Repositories\PermissionMenuRepository;

class PermissionMenuController extends BaseController
{
    /**
     * 后台管理菜单详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $menu = PermissionMenu::find($request->id);

        if (empty($menu)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($menu);
    }

    /**
     * 后台管理菜单列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param PermissionMenuRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(PermissionMenuRepository $repository)
    {
        $lists = $repository->getAll();
        $lists = getTree($lists->toArray(), 0);


        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listFields,
        ]);
    }

    /**
     * 新增后台权限菜单
     *
     * @Author moocde <mo@mocode.cn>
     * @param PermissionMenuRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(PermissionMenuRequest $request)
    {
        $data = $request->only('parent_id', 'route_path', 'route_name', 'frontend_route_path', 'frontend_route_name', 'title', 'icon', 'hide', 'listorder', 'is_dev', 'remark');
        $data['auth_provider'] = 'admin_users';
        $data['hide'] = $request->has('hide') && $data['hide'] == 'true' ? 1 : 0;
        $data['is_dev'] = $request->has('is_dev') && $data['is_dev'] == 'true' ? 1 : 0;

        if (empty($data['route_path']) || PermissionMenu::where('route_path', $data['route_path'])->count() === 0) {
            PermissionMenu::create($data);
            return $this->resSuccess();
        }

        return $this->resFailed(702, '路由地址已存在');
    }

    /**
     * 更新后台管理菜单
     *
     * @Author moocde <mo@mocode.cn>
     * @param PermissionMenuRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(PermissionMenuRequest $request)
    {
        $menu = PermissionMenu::find($request->id);

        if (empty($menu)) {
            return $this->resFailed(704);
        }

        try {
            $data = $request->only('parent_id', 'route_path', 'route_name', 'frontend_route_path', 'frontend_route_name', 'title', 'icon', 'hide', 'listorder', 'is_dev', 'remark');

            if ($menu->id == $data['parent_id']) {
                return $this->resFailed(701, '不能选择当前菜单作为上级菜单');
            }

            $childrens = PermissionMenu::where('auth_provider', 'admin_users')->get();
            $childrens = getAllChildrenId($childrens->toArray(), $menu->id);
            if (in_array($data['parent_id'], $childrens)) {
                return $this->resFailed(701, '不能选择当前菜单的子菜单作为上级菜单');
            }

            $data['hide'] = $request->has('hide') && $data['hide'] == 'true' ? 1 : 0;
            $data['is_dev'] = $request->has('is_dev') && $data['is_dev'] == 'true' ? 1 : 0;
            if ($data['route_path']) {
                $hasRoutePath = PermissionMenu::where('route_path', $data['route_path'])->where('route_path', '!=', $menu->route_path)->count();
                if ($hasRoutePath > 0) {
                    return $this->resFailed(701, '路由地址已存在');
                }
            }
            $menu->update($data);

            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->resFailed(600);
        }
    }

    /**
     * 删除后台管理菜单
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $menu = PermissionMenu::find($request->id);
            if (empty($menu)) {
                return $this->resFailed(700, '删除的数据不存在');
            }
            $hasChilden = PermissionMenu::where('parent_id', $menu->id)->count();
            if ($hasChilden > 0) {
                return $this->resFailed(701, '该菜单有子菜单存在，无法删除！');
            }
            $menu->delete();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->resFailed(600);
        }
    }

    /**
     * 平台路由
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function platformRoutes()
    {
        $hasRoutes = [];
        $menus = PermissionMenu::get();
        foreach ($menus as $value) {
            $hasRoutes[] = $value->route_path;
        }

        $app = app();
        $allRoutes = $app->routes->getRoutes();
        $routes = [];
        foreach ($allRoutes as $value) {
            if (in_array('auth:admin_users', $value->action['middleware']) && !in_array($value->uri, $hasRoutes)) {
                $tmp = [];
                $tmp['route_path'] = $value->uri;
                $tmp['route_name'] = empty($value->action['as']) ? null : $value->action['as'];
                $tmp['methods'] = $value->methods;
                $tmp['middleware'] = $value->action['middleware'];
                $routes[] = $tmp;
            }
        }

        return $this->resSuccess($routes);
    }
}