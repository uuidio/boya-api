<?php
/**
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-25
 * @Time: 14:16
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\ShopAttr;


class ShopAttrController extends BaseController
{

    public function detail(Request $request)
    {
        //$param = $request->only('shop_id');
        $param['shop_id'] = $this->shop->id;

        try {
            $detail = ShopAttr::find($param['shop_id']);

            if (empty($detail)) {
                return $this->resFailed(414, '该店铺没有开通推物功能的权限');
            }

            return $this->resSuccess($detail);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->resFailed(600);
        }
    }

    /**
     * 配置推物功能
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        //$param = $request->only('shop_id', 'promo_goods');

        //获取店铺id
        $param = $request->only('promo_goods');
        $param['shop_id'] = $this->shop->id;

        $param['promo_person'] = $param['promo_goods'] ?? 0;

        if ($param['shop_id'] <= 0)
            return $this->resFailed(414, "参数错误!");

        if (!ShopAttr::where('shop_id', $param['shop_id'])->exists())
            return $this->resFailed(414, '该店铺没有开通分销功能的权限');

        try {
            ShopAttr::find(intval($param['shop_id']))->update($param);
            return $this->resSuccess();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->resFailed(600);
        }

    }


    /**
     * 店铺是否开启
     * @Author hfh_wind
     * @return int
     */
    public function PromoGoodCheck()
    {
        $shop_id = $this->shop->id;

        $shop_promo = ShopAttr::where('shop_id', $shop_id)->first();

        //如果开启推人,才能有推物,商家只能设置推物返利
        if ($shop_promo['promo_person'] == 1 && $shop_promo['promo_good'] == 1) {

            $return['promo_good'] = true;
        } else {
            $return['promo_good'] = false;
        }

        return $this->resSuccess($return);
    }


}
