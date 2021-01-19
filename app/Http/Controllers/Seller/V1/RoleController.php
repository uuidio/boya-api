<?php
/**
 * @Filename        RoleController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Seller\RoleRequest;
use ShopEM\Models\SellerRole;
use ShopEM\Models\SellerRoleMenu;
use ShopEM\Repositories\SellerRoleRepository;

class RoleController extends BaseController
{
    /**
     * 角色列表
     *
     * @Author djw
     * @param SellerRoleRepository $repository
     * @param Request                $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(SellerRoleRepository $repository, Request $request)
    {
        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
        $lists = $repository->search($data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listFields,
        ]);
    }

    /**
     * 角色详情
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $role = SellerRole::find(intval($request->id));

        if(empty($role)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($role);
    }

    /**
     * 新增角色
     *
     * @Author djw
     * @param RoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(RoleRequest $request)
    {
        if (!$request->has('menus') || !is_array($request->menus) || empty($request->menus)) {
            return $this->resFailed(702, '请选择角色相关的权限');
        }

        $data = $request->only('name', 'status', 'remark', 'listorder', 'frontend_extend');
        $data['status'] = $request->has('status') && $data['status'] == 'true' ? 1 : 0;
        $data['shop_id'] = $this->shop->id;

            DB::beginTransaction();
        try {
            $role = SellerRole::create($data);
            $menus = array_map('strval', $request->menus);
            //插入关联表
            SellerRole::find($role->id)->roleToMenus()->sync($menus);

            DB::commit();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            DB::rollBack();
            return $this->resFailed(702);
        }
    }

    /**
     * 更新角色
     *
     * @Author djw
     * @param RoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(RoleRequest $request)
    {
        if (!$request->has('menus') || !is_array($request->menus) || empty($request->menus)) {
            return $this->resFailed(702, '请选择角色相关的权限');
        }

        $role = SellerRole::where('id', intval($request->id))->where('shop_id', $this->shop->id)->first();

        if (empty($role)) {
            return $this->resFailed(704);
        }

        DB::beginTransaction();
        try {
            $data = $request->only('name', 'status', 'remark', 'listorder', 'frontend_extend');
            $data['status'] = $request->has('status') && $data['status'] == 'true' ? 1 : 0;

            $role->update($data);

            $menus = array_map('strval', $request->menus);
            //插入关联表
            $role->roleToMenus()->sync($menus);

            DB::commit();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            DB::rollBack();
            return $this->resFailed(702);
        }
    }

    /**
     * 删除角色
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $role = SellerRole::where('id', intval($request->id))->where('shop_id', $this->shop->id)->first();
            if (empty($role)) {
                return $this->resFailed(700, '删除的数据不存在');
            }
            SellerRoleMenu::where('role_id', $role->id)->delete();
            $role->delete();
            DB::commit();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            DB::rollBack();
            return $this->resFailed(600);
        }
    }
}