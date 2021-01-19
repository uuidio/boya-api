<?php
/**
 * @Filename        ShopSellersController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Seller\SellerAccountRequest;
use ShopEM\Models\SellerAccount;
use ShopEM\Models\ShopRelSeller;
use ShopEM\Repositories\ShopSellerRepository;

class ShopSellersController extends BaseController
{
    /**
     * 管理员列表
     *
     * @Author djw
     * @param ShopSellerRepository $repository
     * @param Request                 $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(ShopSellerRepository $repository, Request $request)
    {
        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
        $data['seller_type'] = 1;

        if(isset($request->per_page)){
            $data['per_page'] = $request->per_page?$request->per_page:10;
        }else{
            $data['per_page'] = config('app.per_page');
        }

        $lists = $repository->search($data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 管理员详情
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $admin = SellerAccount::find(intval($request->id));

        if (empty($admin)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($admin);
    }

    /**
     * 新增管理员
     *
     * @Author djw
     * @param SellerAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(SellerAccountRequest $request)
    {
        $data = $request->only('role_id', 'username', 'email', 'password', 'status');
        $data['password'] = bcrypt($data['password']);
        $data['status'] = $request->has('status') && $data['status'] == 'true' ? 1 : 0;
        $data['seller_type'] = 1;

        if (SellerAccount::where('username', $request->username)->count() > 0) {
            return $this->resFailed(702, '用户名已经存在！');
        }

        DB::beginTransaction();
        try {
            $data['gm_id'] = $this->GMID;
            $sellerAccount = SellerAccount::create($data);
            //记录到关联表
            $rel['gm_id'] = $this->GMID;
            $rel['shop_id'] = $this->shop->id;
            $rel['seller_id'] = $sellerAccount->id;
            $rel['shop_name'] = $this->shop->shop_name;
            ShopRelSeller::create($rel);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());
            return $this->resFailed(702);
        }
        return $this->resSuccess();
    }

    /**
     * 更新管理员
     *
     * @Author djw
     * @param SellerAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(SellerAccountRequest $request)
    {
        $admin = SellerAccount::find(intval($request->id));

        if (empty($admin)) {
            return $this->resFailed(704);
        }

        if (SellerAccount::where('username', $request->username)->where('id', '!=', $admin->id)->count() > 0) {
            return $this->resFailed(702, '用户名已经存在！');
        }

        try {
            $data = $request->only('role_id', 'username', 'email', 'password', 'status');
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = bcrypt($data['password']);
            }
            $data['status'] = $request->has('status') && $data['status'] == 'true' ? 1 : 0;

            $admin->update($data);
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->resFailed(702);
        }
    }

    /**
     * 删除管理员
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $admin = SellerAccount::find(intval($request->id));
            if (empty($admin)) {
                return $this->resFailed(700, '删除的数据不存在');
            }
            $admin->delete();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->resFailed(600);
        }
    }
}