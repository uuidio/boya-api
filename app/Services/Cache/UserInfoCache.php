<?php
/**
 * @Filename        UserInfoCache.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use ShopEM\Models\PlatformRoleMenu;
use ShopEM\Models\GroupRoleMenu;

class UserInfoCache
{

    /**
     * 缓存权限菜单ID
     *
     * @Author moocde <mo@mocode.cn>
     * @param      $user
     * @param bool $delete
     * @return mixed
     */
    public static function platformPermission($user, $delete = false)
    {
        $key = 'platform_permission_menus_' . $user->id;
        if ($delete === true) {
            Cache::forget($key);
        }

        return Cache::rememberForever($key, function () use($user) {
            $menus = PlatformRoleMenu::where('role_id', $user->role_id)->pluck('menu_id');
            return $menus->toArray();
        });
    }

    /**
     * 删除权限菜单ID缓存
     *
     * @Author moocde <mo@mocode.cn>
     * @param $user
     */
    public static function deletePlatformPermission($user)
    {
        Cache::forget('platform_permission_menus_' . $user->id);
    }

    /**
     * 缓存权限菜单ID
     *
     * @Author moocde <mo@mocode.cn>
     * @param      $user
     * @param bool $delete
     * @return mixed
     */
    public static function groupPermission($user, $delete = false)
    {
        $key = 'group_permission_menus_' . $user->id;
        if ($delete === true) {
            Cache::forget($key);
        }

        return Cache::rememberForever($key, function () use($user) {
            $menus = GroupRoleMenu::where('role_id', $user->role_id)->pluck('menu_id');
            return $menus->toArray();
        });
    }

    /**
     * 删除权限菜单ID缓存
     *
     * @Author moocde <mo@mocode.cn>
     * @param $user
     */
    public static function deleteGroupPermission($user)
    {
        Cache::forget('group_permission_menus_' . $user->id);
    }
}