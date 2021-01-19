<?php
/**
 * @Filename        BaseController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Group;

use ShopEM\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ShopEM\Models\GroupManageUser;
use ShopEM\Models\GroupManageLog;

class BaseController extends Controller
{
    protected $groupUser;


    public function __construct()
    {
        $this->groupUser = $this->getGroupUser();
    }

    /**
     * 登录管理员信息
     *
     * @Author hfh_wind
     * @return null
     */
    public function getGroupUser()
    {
        if (Auth::guard('group_users')->check()) {
            $platform_id = Auth::guard('group_users')->user()->id;
            return Cache::remember('cache_key_group_user_id_' . $platform_id, cacheExpires(), function () use ($platform_id) {
                return GroupManageUser::find($platform_id);
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
            'admin_user_id'   => $this->groupUser->id,
            'admin_user_name' => $this->groupUser->username,
            'memo'           => $memo,
            'status'         => ($status ? 1 : 0),
            'router'         => request()->fullurl(),
            'ip'             => request()->getClientIp(),
        );

        //system_tasks_adminlog
        GroupManageLog::create($queue_params);
        return true;
    }

}

