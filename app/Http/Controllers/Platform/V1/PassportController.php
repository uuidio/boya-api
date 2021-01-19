<?php
/**
 * @Filename PassportController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\LoginRequest;
use ShopEM\Models\AdminUsers;
use ShopEM\Models\PermissionMenu;
use ShopEM\Models\PlatformRoleMenu;
use ShopEM\Models\GmPlatform;
use ShopEM\Services\Cache\UserInfoCache;
use ShopEM\Traits\ProxyOauth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


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
        $user = AdminUsers::where('username', $request->username)->first();

        if (empty($user)) {
            return $this->resFailed(402);
        }

        if ($user['status'] == 0) {
            return $this->resFailed(401, '账号未启用');
        }
        $gmPlatform = GmPlatform::where('gm_id',$user->gm_id)->first();
        if ($gmPlatform->status != 1 && $gmPlatform->allow_login != 1) {
            return $this->resFailed(401, '项目未开启');
        }

        if ($user->role_id && $user->is_root == 0) {
            $role = \ShopEM\Models\PlatformRole::find($user->role_id);
            if (!$role || $role['status'] == 0) {
                return $this->resFailed(401, '角色未启用');
            }
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->resFailed(402);
        }

        $token = $this->authenticate('admin_users');

        if (!$token) {
            return $this->resFailed(402);
        }

        $menuIds = PlatformRoleMenu::where('role_id', $user->role_id)->pluck('menu_id');
        $rule = [];
        if (!empty($menuIds)) {
            $frontendPermission = PermissionMenu::whereIn('id', $menuIds)->get('frontend_route_path');
            if (!empty($frontendPermission)) {
                foreach ($frontendPermission as $item) {
                    if (!in_array($item->frontend_route_path, $rule) && $item->frontend_route_path != null) {
                        $rule[] = $item->frontend_route_path;
                    }
                }
            }
        }
        unset($frontendPermission);

        UserInfoCache::platformPermission($user, true);
        $userInfo = [];
        $userInfo['id'] = $user->id;
        $userInfo['role_id'] = $user->role_id;
        $userInfo['username'] = $user->username;
        $userInfo['type'] = $gmPlatform->type??'normal';
        $userInfo['groupname'] = $gmPlatform->platform_name;
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
        if (Auth::guard('admin_users')->check()) {
            Auth::guard('admin_users')->user()->token()->delete();
        }

        return $this->resSuccess();
    }

    public function test()
    {

    }
}
