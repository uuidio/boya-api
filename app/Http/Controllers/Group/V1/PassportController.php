<?php
/**
 * @Filename PassportController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Http\Requests\Group\LoginRequest;
use ShopEM\Models\GroupManageUser;
use ShopEM\Models\GroupPermissionMenu;
use ShopEM\Models\GroupRoleMenu;
use ShopEM\Services\Cache\UserInfoCache;
use ShopEM\Traits\ProxyOauth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PassportController extends BaseController
{

    use ProxyOauth;

    /**
     * 平台登录
     *
     * @Author moocde <mo@mocode.cn>
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(LoginRequest $request)
    {
        $user = GroupManageUser::where('username', $request->username)->first();

        if (empty($user)) {
            return $this->resFailed(402);
        }

        if ($user['status'] == 0) {
            return $this->resFailed(401, '账号未启用');
        }

        if ($user->role_id && $user->is_root == 0) {
            $role = \ShopEM\Models\GroupRole::find($user->role_id);
            if (!$role || $role['status'] == 0) {
                return $this->resFailed(401, '角色未启用');
            }
        }

//        if (!Hash::check($request->password, $user->password)) {
//            return $this->resFailed(402);
//        }

        $token = $this->authenticate('group_users');
        if (!$token) {
            return $this->resFailed(402);
        }

        $menuIds = GroupRoleMenu::where('role_id', $user->role_id)->pluck('menu_id');
        $rule = [];
        if (!empty($menuIds)) {
            $frontendPermission = GroupPermissionMenu::whereIn('id', $menuIds)->get('frontend_route_path');
            if (!empty($frontendPermission)) {
                foreach ($frontendPermission as $item) {
                    if (!in_array($item->frontend_route_path, $rule) && $item->frontend_route_path != null) {
                        $rule[] = $item->frontend_route_path;
                    }
                }
            }
        }
        unset($frontendPermission);

        UserInfoCache::groupPermission($user, true);
        $userInfo = [];
        $userInfo['id'] = $user->id;
        $userInfo['role_id'] = $user->role_id;
        $userInfo['username'] = $user->username;
        $userInfo['status'] = $user->status;
        $userInfo['is_root'] = $user->is_root;
        $userInfo['frontend_permission'] = $rule;

        return $this->resSuccess(array_merge($userInfo, $token));
    }

    /**
     * 退出
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public function logout()
    {
        if (Auth::guard('group_users')->check()) {
            Auth::guard('group_users')->user()->token()->delete();
        }

        return $this->resSuccess();
    }


}
