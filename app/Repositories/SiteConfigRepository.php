<?php
/**
 * @Filename        SiteConfigRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\Goods;
use ShopEM\Models\SiteConfig;
use Illuminate\Support\Facades\Cache;

class SiteConfigRepository
{
    /**
     * 获取站点配置
     *
     * @Author moocde <mo@mocode.cn>
     * @param $page
     * @return mixed
     */
    public function configItems($page,$gm_id=0)
    {
        $config = [];
        $items = SiteConfig::where('page', $page);
        if($gm_id>0){
            $items = $items->where('gm_id',$gm_id);
        }
        $items = $items->get();

        foreach ($items as $item) {
            $config[$item['group']] = unserialize($item['value']);

            if ($item['group'] == 'goods_swiper' || $item['group'] == 'hot_goods') {

                if (isset($config[$item['group']][0]['value'])) {
                    foreach ($config[$item['group']] as $k => $v) {
                        foreach ($v['value'] as $key => $value) {
                            $goods_info = Goods::where(['id' => $value])->select('goods_image', 'goods_name',
                                'goods_price', 'goods_marketprice', 'goods_salenum', 'id')->where('goods_state', 1)->first();

                            if (!empty($goods_info)) {
                                $config[$item['group']][$k]['value'][$key] = $goods_info;
                            } else {
                                unset($config[$item['group']][$k]['value'][$key]);
                            }
                        }
                        $config[$item['group']][$k]['value'] = array_values($config[$item['group']][$k]['value']);
                    }
                }
            }
        }

        return $config;
    }


    /**
     * 获取挂件配置
     * @Author hfh_wind
     * @param $page
     * @return array
     */
    public function configItems_v1($page,$custom_id=0,$gm_id=0,$cache=true)
    {
        $cache_key = 'CONFIGITEMS_V1_PAGE_'.$page.'_CUSTOM_'.$custom_id.'_GM_'.$gm_id;
        if (Cache::has($cache_key) && $cache) {
            $configCache = Cache::get($cache_key);
            if (!empty($configCache)) {
                return $configCache;
            }
            Cache::forget($cache_key);
        }

        $config = [];
        $items = SiteConfig::where('page', $page);
        if($custom_id>0){
            $items = $items->where('custom_id',$custom_id);
        }
        if($gm_id>0){
            $items = $items->where('gm_id',$gm_id);
        }
        $items = $items->get();
        
        $repository = new ConfigRepository; 
        $repository->newConfigItems($items,$gm_id,$config);

        Cache::forget($cache_key);
        Cache::put($cache_key, $config, now()->addMinutes(60));
        // Cache::forever($cache_key, $config);
        return $config;
    }


    /**
     * 首页下拉获取热卖商品
     * @Author huiho
     * @return mixed
     */
    public function getIndexHot($page,$gm_id=0)
    {
        $items = SiteConfig::where('group', 'hot_goods')
                            ->where('page', $page);
        if($gm_id>0){
            $items = $items->where('gm_id',$gm_id);
        }
        $items = $items->get();
                           
        $config = unserialize($items[0]['value']);
        $itemIds = $config[0]['value'];
        $lists = Goods::select('goods_image', 'goods_name',
                        'goods_price', 'goods_marketprice', 'goods_salenum', 'id')
                        ->whereIn('id' , $itemIds)
                        ->where('goods_state', 1)
                        ->orderBy('id', 'desc')
                        ->paginate(config('app.per_page'));

        return $lists;
    }



}