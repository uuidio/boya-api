<?php
/**
 * @Filename        ShopController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\SellerAccount;
use Illuminate\Support\Facades\DB;
use ShopEM\Repositories\ShopSellerRepository;
use ShopEM\Http\Requests\Platform\SellerAccountRequest;
use ShopEM\Http\Requests\Platform\SellerAccountResetRequest;
use ShopEM\Http\Requests\Platform\SellerAccountResetTypeRequest;

class ShopSellersController extends BaseController
{

    /**
     * 店主列表
     *
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function Lists(Request $request, ShopSellerRepository $ShopSellerRepository)
    {
        $input_data = $request->all();

        if(isset($request->per_page)){
            $input_data['per_page'] = $request->per_page?$request->per_page:10;
        }else{
            $input_data['per_page'] = config('app.per_page');
        }
        $input_data['gm_id'] = $this->GMID;
        $lists = $ShopSellerRepository->search($input_data);
        $repository = new \ShopEM\Repositories\ShopRepository();
        foreach ($lists as $key => $value) {
            $shop = [
                'data'  => DB::table('shops')->select('shops.*')->leftJoin('shop_rel_sellers',
                    'shop_rel_sellers.shop_id', '=', 'shops.id')->where('shop_rel_sellers.seller_id',
                    $value->id)->get(),
                'field' => $repository->listShowFields(),
            ];
            $lists[$key]['bind_shop'] = $shop;
        }

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $ShopSellerRepository->listShowFields(),
        ]);
    }


    /**
     * 创建商家账号
     *
     * @Author hfh_wind
     * @param SellerAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAccount(SellerAccountRequest $request)
    {
        $seller = $request->only('username', 'password');
        $seller['password'] = bcrypt($seller['password']);

        DB::beginTransaction();
        try {

            $flag = SellerAccount::where('username', $seller['username'])->count();
            if ($flag) {
                return $this->resFailed(701, '用户名已经存在!');
            }
            $seller['gm_id'] = $this->GMID;
            SellerAccount::create($seller);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog("创建店铺管理账号" . $seller['username'], 0);
            return $this->resFailed(702, $e->getMessage());
        }
        //日志
        $this->adminlog("创建店铺管理账号" . $seller['username'], 1);

        return $this->resSuccess();
    }


    /**
     * 修改密码
     *
     * @Author moocde <mo@mocode.cn>
     * @param SellerAccountResetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sellerResetPwd(SellerAccountResetRequest $request)
    {
        $request = $request->only('id', 'password');
        $id = $request['id'];
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        try {
            $sellerAccount = SellerAccount::find($id);
            if (empty($sellerAccount)) {
                return $this->resFailed(701, '找不到商家账号!');
            }
            $account['password'] = bcrypt($request['password']);

            SellerAccount::where(['id' => $id])->update($account);
        } catch (\Exception $e) {

            //日志
            $this->adminlog("店铺管理账号" . $sellerAccount['username'] . "修改密码", 0);
            return $this->resFailed(701, $e->getMessage());
        }

        //日志
        $this->adminlog("店铺管理账号" . $sellerAccount['username'] . "修改密码", 1);

        return $this->resSuccess();
    }

    /**
     * 修改商家账户角色
     *
     * @Author zhh
     * @param SellerAccountResetTypeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sellerResetType(SellerAccountResetTypeRequest $request)
    {
        //获取商户id
        $id = $request['id'];
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        try {
            //核对商户id
            $sellerAccount = SellerAccount::find($id)->toArray();
            if (empty($sellerAccount)) {
                return $this->resFailed(701, '找不到商家账号!');
            }

            //当账户为店员时
            if ($sellerAccount['seller_type'] == 1) {
                $sellerAccount['seller_type'] = 0;
                SellerAccount::where(['id' => $id])->update($sellerAccount);
                return $this->resSuccess('更新成功，已经升级账户为店长');
            }
            //当账户为店长时
            if ($sellerAccount['seller_type'] == 0) {
                $sellerAccount['seller_type'] = 1;
                SellerAccount::where(['id' => $id])->update($sellerAccount);
                return $this->resSuccess('更新成功，已经更新账户为店员');
            }

        } catch (\Exception $e) {
            //日志
            $this->adminlog("店铺管理账号" . $sellerAccount['username'] . "修改角色", 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog("店铺管理账号" . $sellerAccount['username'] . "修改角色", 1);
    }

    /**
     * [account_switch 商家账户开关（0禁用1启用）]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function account_switch(Request $request)
    {
        if (!$request->filled('id') || !$request->has('statue')) {
            return $this->resFailed(414, '参数不全');
        }
        $obj = SellerAccount::find($request->id);
        if(empty($obj)){
            return $this->resFailed(701, '找不到账号!');
        }

        $obj->status = $request->statue;
        if ($obj->status) {
            $msg = "启用";
        } else {
            $msg = "禁用";
        }
        $msg_text="店铺管理账号" . $obj['username']. $msg;
        try {

            $obj->save();

        } catch (\Exception $e) {
            //日志
            $this->adminlog( $msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }
}