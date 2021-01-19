<?php
/**
 * @Filename        BaseController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Controllers\Live;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Controller;
use ShopEM\Repositories\LiveUsersRepository;
use ShopEM\Repositories\AssistantUsersRepository;

class BaseController extends Controller
{
    protected $user;
    protected $assistant;


    public function __construct()
    {
        $this->user = $this->userinfo();
        $this->assistant = $this->assistantinfo();
    }

    /**
     * 会员信息
     *
     * @Author linzhe
     * @return mixed|null
     */
    public function userinfo()
    {

        if (Auth::guard('live_users')->check()) {
            $user_id = Auth::guard('live_users')->user()->id;
            return Cache::remember('cache_key_live_id_' . $user_id, cacheExpires(), function () use ($user_id) {
                $repository = new LiveUsersRepository();
                $user_info = $repository->userinfo($user_id);
                return $user_info;

            });
        }
        return null;
    }

    /**
     * 会员信息
     *
     * @Author linzhe
     * @return mixed|null
     */
    public function assistantinfo()
    {

        if (Auth::guard('assistant_users')->check()) {
            $user_id = Auth::guard('assistant_users')->user()->id;
            return Cache::remember('cache_key_assistant_id_' . $user_id, cacheExpires(), function () use ($user_id) {
                $repository = new AssistantUsersRepository();
                $user_info = $repository->userinfo($user_id);

                return $user_info;

            });
        }
        return null;
    }


}