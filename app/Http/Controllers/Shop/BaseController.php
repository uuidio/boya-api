<?php
/**
 * @Filename        BaseController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Controller;
use ShopEM\Repositories\UserAccountRepository;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Jobs\UpdateCrmUserInfo;

class BaseController extends Controller
{
    protected $user;

    protected $GMID;
    /**
     * [$isGroupApp 是否是集团版小程序]
     * @var boolean
     */
    protected $noGroupApp = false;


    public function __construct()
    {
        $this->user = $this->userinfo();
        $GmToken = \Request::header('GmToken');
        $this->GMID = $this->getGmid($GmToken);
    }

    /**
     * 会员信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed|null
     */
    public function userinfo()
    {
        if (Auth::guard('shop_users')->check()) {
            $user_id = Auth::guard('shop_users')->user()->id;
            return Cache::remember('cache_key_user_id_' . $user_id, cacheExpires(), function () use ($user_id) {
                $repository = new UserAccountRepository();
                $user_info = $repository->userinfo($user_id);

                return $user_info;

            });
        }
        return null;
    }


    public function getGmid($GmToken)
    {
        $GmId = intval($GmToken)>0? intval($GmToken) : 1;
        $status = Cache::remember('cache_gm_token_status_' . $GmId, cacheExpires(), function () use ($GmId) {
            return GmPlatform::where('gm_id',$GmId)->value('status');
        });
        if($status == 0)
        {
            if (Auth::guard('shop_users')->check()) {
                $user_id = Auth::guard('shop_users')->user()->id;
            }else{
                $user_id = md5(request()->getClientIp());
            }
            $GmId = Cache::remember('cache_'.$user_id.'_token_status_' . $GmId, cacheExpires(), function (){
                return GmPlatform::where('status',1)->value('gm_id');
            });
        }
        if ($status > 0) 
        {
            if (Auth::guard('shop_users')->check()) 
            {
                $user_id = Auth::guard('shop_users')->user()->id;
                Cache::forget('cache_'.$user_id.'_token_status_' . $GmId);
                $this->isRelYitianInfo( $user_id, $GmId );
            }
        }
        /**
         * 默认注册集团项目
         */
        if (Auth::guard('shop_users')->check()) 
        {
            // $this->isRelYitianInfo( $user_id, GmPlatform::gmSelf() );
        }

        $request = \Request::only('gm_id');
        if (isset($request['gm_id']) && $request['gm_id']>0) {
            return intval($request['gm_id']);
        };
        return $GmId;
    }



    //判断是否关联账号
    public function isRelYitianInfo($user_id,$gm_id)
    {
        $data['user_id'] = $user_id;
        $data['mobile'] = $this->user->mobile;
        $data['gm_id'] = $gm_id;

        $doesntExist = UserRelYitianInfo::where($data)->doesntExist();
        if (!$doesntExist) {
            $this->updateCrmUserInfoJob($data);
        }
        if ($doesntExist) 
        {
            try {
                $user = UserRelYitianInfo::create($data);
                $this->updateCrmUserInfoJob($data);
            } catch (Exception $e) {
                return true;
            }
        }
    }

    public function updateCrmUserInfoJob($user)
    {
        $data['user_id']= $user['user_id'];
        $data['mobile'] = $user['mobile'];
        $data['gm_id']  = $user['gm_id'];
        $data['updateCardTypeCode'] = true;
        UpdateCrmUserInfo::dispatch($data);
    }
}