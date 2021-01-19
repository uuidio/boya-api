<?php
/**
 * @Filename        RoleController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Http\Requests\Platform\RoleRequest;
use ShopEM\Models\GroupRole;
use ShopEM\Models\GroupRoleMenu;
use ShopEM\Repositories\GroupRoleRepository;

class RoleController extends BaseController
{
    /**
     * 角色列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param PlatformRoleRepository $repository
     * @param Request                $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(GroupRoleRepository $repository, Request $request)
    {
        $lists = $repository->search($request->all());

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listFields,
        ]);
    }


     /**
     * 新增角色
     *
     * @Author moocde <mo@mocode.cn>
     * @param RoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(RoleRequest $request)
    {
        if (!$request->has('menus') || !is_array($request->menus) || empty($request->menus)) {
            return $this->resFailed(702, '请选择角色相关的权限');
        }

        $data = $request->only('name', 'status', 'is_root', 'remark', 'listorder', 'frontend_extend');
        $data['status'] = $request->has('status') && $data['status'] == 'true' ? 1 : 0;
        $data['is_root'] = $request->has('is_root') && $data['is_root'] == 'true' ? 1 : 0;

        DB::beginTransaction();
        try {
            $role = GroupRole::create($data);
            $menus = array_map('intval', $request->menus);
            //插入关联表
            GroupRole::find($role->id)->roleToMenus()->sync($menus);

            DB::commit();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            DB::rollBack();
            return $this->resFailed(702);
        }
    }

    /**
     * 角色详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $role = GroupRole::find(intval($request->id));

        if(empty($role)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($role);
    }

    /**
     * 删除角色
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $role = GroupRole::find(intval($request->id));
            if (empty($role)) {
                return $this->resFailed(700, '删除的数据不存在');
            }
            GroupRoleMenu::where('role_id', $role->id)->delete();
            $role->delete();
            DB::commit();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            DB::rollBack();
            return $this->resFailed(600);
        }
    }

      /**
     * 更新角色
     *
     * @Author moocde <mo@mocode.cn>
     * @param RoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(RoleRequest $request)
    {
        if (!$request->has('menus') || !is_array($request->menus) || empty($request->menus)) {
            return $this->resFailed(702, '请选择角色相关的权限');
        }

        $role = GroupRole::find(intval($request->id));

        if (empty($role)) {
            return $this->resFailed(704);
        }

        DB::beginTransaction();
        try {
            $data = $request->only('name', 'status', 'is_root', 'remark', 'listorder', 'frontend_extend');
            $data['status'] = $request->has('status') && $data['status'] == 'true' ? 1 : 0;
            $data['is_root'] = $request->has('is_root') && $data['is_root'] == 'true' ? 1 : 0;

            $role->update($data);

            $menus = array_map('intval', $request->menus);
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

}