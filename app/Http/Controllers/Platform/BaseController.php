<?php
/**
 * @Filename        BaseController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform;

use ShopEM\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ShopEM\Models\AdminUsers;
use ShopEM\Models\PlatformAdminLogs;

class BaseController extends Controller
{
    protected $platform;

    protected $GMID;

    public function __construct()
    {
        $this->platform = $this->getPlatform();
        $this->GMID     = $this->platform->gm_id??1;
    }

    /**
     * 登录管理员信息
     *
     * @Author hfh_wind
     * @return null
     */
    public function getPlatform()
    {
        if (Auth::guard('admin_users')->check()) {

            $platform_id = Auth::guard('admin_users')->user()->id;
            return Cache::remember('cache_key_splatform_id_' . $platform_id, cacheExpires(), function () use ($platform_id) {
                return AdminUsers::find($platform_id);
            });
        }
        return null;
    }


    /**
     * 记录平台操作日志
     *
     * @param $lang 日志语言包
     * @param $status 成功失败状态
     * @param $admin_name
     * @param $admin_id
     */
    public  function adminlog($memo = '', $status = 1)
    {
        // 开启了才记录操作日志
        if ( env('ADMIN_OPERATOR_LOG', '') !== true ) return false;

        $queue_params = array(
            'admin_user_id'   => $this->platform->id,
            'admin_user_name' => $this->platform->username,
            'memo'           => $memo,
            'status'         => ($status ? 1 : 0),
            'router'         => request()->fullurl(),
            'ip'             => request()->getClientIp(),
            'gm_id'          => $this->GMID,
        );

        //system_tasks_adminlog
        PlatformAdminLogs::create($queue_params);
        return true;
    }

}