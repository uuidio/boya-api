<?php
/**
 * @Filename        ShopController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Platform\ShopRequest;
use ShopEM\Http\Requests\Seller\ShopApplyRequest;
use ShopEM\Models\Shop;
use ShopEM\Models\ShopInfo;
use ShopEM\Models\ShopRelSeller;
use Illuminate\Support\Facades\DB;
use ShopEM\Repositories\ShopRepository;
use ShopEM\Repositories\LogisticsRepository;
use Illuminate\Http\Request;

class ShopController extends BaseController
{
    /**
     * 店铺详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param ShopRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(ShopRepository $repository)
    {
        return $this->resSuccess($repository->detail($this->shop->id));
    }

    /**
     * 更新店铺
     *
     * @Author moocde <mo@mocode.cn>
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShopRequest $request)
    {
        $data = $request->only('shop_name', 'class_id', 'company_name', 'province_id', 'city_id', 'area_id', 'street_id', 'address', 'zip_code', 'shop_logo', 'shop_banner', 'shop_phone', 'shop_keywords', 'shop_description', 'is_recommend', 'is_own_shop', 'point_id', 'housing_id', 'post_fee');
        try {
            $shop = Shop::find($this->shop->id);
            if (empty($shop)) {
                return $this->resFailed(701);
            }
            $flag = Shop::where('shop_name', $data['shop_name'])->where('gm_id',$shop->gm_id)->where('id', '!=', $this->shop->id)->count();
            if ($flag) {
                return $this->resFailed(701, '店铺名称已存在!');
            }
            $shop->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 平台可提供的物流列表
     * @Author hfh_wind
     * @param Request $request
     * @param LogisticsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function logisticsLists(Request $request, LogisticsRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');
        //显示允许展示
        $input_data['is_show']=1;
        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 商家申请开店
     *
     * @Author hfh_wind
     * @return array
     */
    public function enterApply(ShopApplyRequest $request)
    {

        $postData = $request->only('shop_name', 'shop_type', 'company_name', 'license_num', 'license_img', 'representative', 'corporate_identity', 'license_area', 'license_addr', 'establish_date', 'license_indate', 'enroll_capital', 'scope', 'shop_url', 'company_area', 'company_addr', 'company_phone', 'company_contacts', 'company_cmobile', 'tissue_code',
            'tissue_code_img', 'tax_code', 'tax_code_img', 'bank_user_name', 'bank_name', 'cnaps_code', 'bankID', 'bank_area');

        $postData['seller_id'] = $this->shop->id;

        DB::beginTransaction();
        try {
            $flag = ShopRelSeller::where('seller_id', $this->shop->id)->count();
            if ($flag) {
                throw new \Exception('该账号下已有店铺!');
            }

            //先生成一个店铺,处于待审核状态
            $shop_info = [
                'shop_name' => $postData['shop_name'],
                'shop_type' => $postData['shop_type'],
                'status' => 'active',
            ];
            $shop_id = Shop::create($shop_info);
            $postData['shop_id'] = $shop_id->id;


            $ShopRelSeller = [
                'shop_id' => $postData['shop_id'],
                'seller_id' => $postData['seller_id'],
                'shop_name' => $postData['shop_name'],
            ];

            ShopRelSeller::create($ShopRelSeller);
            unset($postData['shop_name']);
            unset($postData['shop_type']);

            ShopInfo::create($postData);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('提交资料失败' . $e->getMessage());
        }
        DB::commit();
        return $this->resSuccess([], '请稍后,已经提交后台审核!');
    }


}