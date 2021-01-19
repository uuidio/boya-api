<?php
/**
 * @Filename PassportController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Platform\LoginRequest;
use ShopEM\Http\Requests\Seller\SignupUserRequest;
use ShopEM\Http\Requests\Seller\ResetPwdRequest;
use ShopEM\Models\SellerAccount;
use ShopEM\Models\SellerRoleMenu;
use ShopEM\Models\Shop;
use ShopEM\Models\GmPlatform;
use ShopEM\Services\ShopsService;
use ShopEM\Traits\ProxyOauth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class PassportController extends BaseController
{

    use ProxyOauth;

    /**
     * 登录
     *
     * @Author moocde <mo@mocode.cn>
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(LoginRequest $request)
    {
        $hasUser = SellerAccount::where('username', $request->username)->first();

        if (empty($hasUser)) {
            return $this->resFailed(402);
        }

        if ($hasUser['status'] == 0) {
            return $this->resFailed(401, '账号未启用');
        }
        $gm = GmPlatform::where('gm_id',$hasUser->gm_id)->select('status','allow_login')->first();
        if ($gm->status != 1 && $gm->allow_login != 1) {
            return $this->resFailed(401, '项目未开启');
        }

        if ($hasUser->role_id) {
            $role = \ShopEM\Models\SellerRole::find($hasUser->role_id);
            if (!$role || $role['status'] == 0) {
                return $this->resFailed(401, '角色未启用');
            }
        }

        if (!Hash::check($request->password, $hasUser->password)) {
            return $this->resFailed(402);
        }

        //判断是否有绑定店铺
        $shop_obj= new Shop();
        $hasShop = $shop_obj->leftJoin('shop_rel_sellers','shop_rel_sellers.shop_id','=','shops.id')
            ->leftJoin('seller_accounts','seller_accounts.id','=','shop_rel_sellers.seller_id')
            ->where(['shop_rel_sellers.seller_id'=>$hasUser->id])->select('shops.*')->first();
        if (empty($hasShop)) {
            return $this->resFailed(402, '未开通店铺');
        }
        if ($hasShop->shop_state <= 0) {
            return $this->resFailed(402, '该店铺被关闭');
        }

        $token = $this->authenticate('seller_users');

        if (!$token) {
            return $this->resFailed(402);
        }

        $rule = SellerRoleMenu::where('role_id', $hasUser->role_id)->pluck('menu_name');

        $userInfo = [];
        $userInfo['id'] = $hasUser->id;
        $userInfo['role_id'] = $hasUser->role_id;
        $userInfo['username'] = $hasUser->username;
        $userInfo['shopname'] = $hasShop->shop_name;
        $userInfo['status'] = $hasUser->status;
        $userInfo['seller_type'] = $hasUser->seller_type;
        $userInfo['frontend_permission'] = $rule;

        return $this->resSuccess(array_merge($userInfo, $token));
    }


    /**
     * 完成注册流程 ,后续是提交入驻信息审核
     *
     * @Author hfh_wind
     * @param PasspordRequest $request
     * @return array|\Illuminate\Http\JsonResponse
     * @throws \Exception
     * @throws \ShopEM\Services\User\Exception
     */
    public function doRegister(SignupUserRequest $request)
    {
        $userInfo = $request->all();

        $accountUser_data = ShopsService::signupUser($userInfo);

        DB::beginTransaction();
        try {

            SellerAccount::create($accountUser_data);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $msg = $e->getMessage();
            throw new \Exception('商家注册失败' . $msg);
        }

        $token = $this->authenticate('seller_users');


        if (!$token) {
            return $this->resFailed(402);
        }

        return $this->resSuccess($token,'商家身份注册成功,下一步提交资料');
    }


    /**
     * 退出
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public function logout()
    {
        if (Auth::guard('seller_users')->check()) {
            Auth::guard('seller_users')->user()->token()->delete();
        }

        return $this->resSuccess();
    }


    public function resetPwd(ResetPwdRequest $request)
    {
        $accout = SellerAccount::where('username', $request->username)->first();
        if (!Hash::check($request->old_password, $accout->password)) {
            return $this->resFailed(402,'原密码不正确');
        }
        DB::beginTransaction();
        try {

            $accout->password = bcrypt($request->password);
            $accout->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->resFailed(402,'修改密码失败');
        }
        return $this->resSuccess([],'修改成功，重新登录');
    }


}
