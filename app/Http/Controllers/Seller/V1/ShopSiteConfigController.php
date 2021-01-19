<?php
/**
 * @Filename ShopSiteConfigController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Seller\ShopTmplRequest;
use ShopEM\Models\ShopSiteConfig;
use Illuminate\Support\Facades\DB;
use ShopEM\Repositories\ShopSiteConfigRepository;

class ShopSiteConfigController extends BaseController
{
    /**
     * 添加店铺挂件
     *
     * @Author hfh
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function TmplStore(Request $request)
    {
        $data = $request->only('page', 'group', 'value');

        $data['value'] = empty($data['value']) ? [] : $data['value'];

        if (empty($data['page'])) {
            return $this->resFailed(702, '提交参数错误');
        }
        $shop_id = $this->shop->id;

//        $content =json_decode($data['value'],true);
        $content = $data['value'];

        $ids = [];
        DB::beginTransaction();
        try {
            foreach ($content as $key => $value) {

                $data['value'] = $value;
                $data['group'] = $value['id'];
                $data['shop_id'] = $shop_id;

                if (isset($value['site_id'])) {
                    $site_id = $value['site_id'];
                    $hasConfig = ShopSiteConfig::where('page', $value['id'])->where('shop_id', $shop_id)->where('id',
                        $value['site_id'])->first();

                    if (!empty($hasConfig)) {
                        $hasConfig->update($data);
                    } else {
                        //如果有id但找不到的情况下,创建
                        $newSite = ShopSiteConfig::create($data);
                        $site_id = $newSite['id'];
                    }

                } else {
                    //属于新建的
                    $newSite = ShopSiteConfig::create($data);
                    $site_id = $newSite['id'];
                }
                $ids[$key] = $site_id;
            }

            ShopSiteConfig::where('page', $data['page'])->where('shop_id', $shop_id)->whereNotIn('id', $ids)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = $e->getMessage();

            throw new \logicexception($msg);
        }

        Cache::forget('config_shop_v1_page_'.$data['page']);

        return $this->resSuccess();
    }

    /**
     * 获取店铺挂件配置
     * @Author hfh
     * @param ShopTmplRequest $request
     * @param ShopSiteConfigRepository $repository
     * @return mixed
     */
    public function Items(ShopTmplRequest $request, ShopSiteConfigRepository $repository)
    {
        $data = $request->only('page','type');

        $data['shop_id'] = $this->shop->id;

        $config_items = $repository->configItems_v1($data);

        return $this->resSuccess($config_items);
    }


    /**
     * 添加店铺配置
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SiteStore(Request $request)
    {
        $data = $request->only('page', 'group', 'value');
        $data['value'] = empty($data['value']) ? [] : $data['value'];
        if (empty($data['page'])) {
            return $this->resFailed(702, '提交参数错误');
        }

        $shop_id = $this->shop->id;


        $data['shop_id'] = $shop_id;

        $hasConfig = ShopSiteConfig::where('page', $data['page'])->where('shop_id', $shop_id)->first();
        if (empty($hasConfig)) {
            ShopSiteConfig::create($data);
        } else {
            $hasConfig->update($data);
        }
        Cache::forget('config_shop_v1_page_'.$data['page']);

        return $this->resSuccess();
    }


}