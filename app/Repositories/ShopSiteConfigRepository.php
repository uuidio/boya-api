<?php
/**
 * @Filename        ShopSiteConfigRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use ShopEM\Models\Goods;
use ShopEM\Models\ShopSiteConfig;

class ShopSiteConfigRepository
{
    /**
     * 店铺挂件配置
     * @Author hfh
     * @param $page
     * @return mixed
     */
    public function configItems($page)
    {
        $config = [];

        if($page['type']=='site'){

            $items = ShopSiteConfig::where(['page'=>$page['page'],'shop_id'=>$page['shop_id']])->first();

            if($items) {
                //修改站点配置的数据格式
                $config_info = $items['value'];
                foreach ($config_info as $k => $v) {
                    $v['key'] = $k;
                    $config[] = $v;
                }
            }

        }else{
            $items = ShopSiteConfig::where(['page'=>$page['page'],'shop_id'=>$page['shop_id']])->get();

            foreach ($items as $key => $item) {
                $config[$key] = $item['value'];
                $config[$key]['site_id'] = $item['id'];

                if ($item['group'] == 'goodGroup') {

                    foreach ($config[$key]['options'] as $key_item => $value) {

                        $goods_info = Goods::where(['id' => $value])->select('goods_image', 'goods_name',
                            'goods_price', 'goods_marketprice', 'goods_salenum', 'id')->first();

                        if (!empty($goods_info)) {
                            $config[$key]['options'][$key_item] = $goods_info;
                        } else {

                            unset($config[$key]['options'][$key_item]);
                        }
                    }
                }

            }
        }


        return $config;
    }


    /**
     * 获取挂件配置
     * @Author hfh
     * @param $param
     * @return mixed
     */
    public function configItems_v1($param)
    {
        Cache::forget('config_shop_v1_page_'.$param['page']);
        $config = Cache::rememberForever('config_shop_v1_page_'.$param['page'], function() use ($param) {

            $config = [];
            if($param['type']=='site'){

                $items = ShopSiteConfig::where(['page'=>$param['page'],'shop_id'=>$param['shop_id']])->first();
                if($items) {
                    //修改站点配置的数据格式
                    $config_info = $items['value'];
                    foreach ($config_info as $k => $v) {
                        $v['key'] = $k;
                        $config[] = $v;
                    }
                }

            }else {

                $items = ShopSiteConfig::where(['page'=>$param['page'],'shop_id'=>$param['shop_id']])->get();

                if(count($items) >0){
                    foreach ($items as $key => $item) {

                        $config[$key] = $item['value'];
                        $config[$key]['site_id'] = $item['id'];

                        if ($item['group'] == 'goodGroup' || $item['group'] == 'GoodSwiper') {

                            foreach ($config[$key]['options'] as $key_item => $value) {

                                $goods_info = Goods::where(['id' => $value])->select('goods_image', 'goods_name',
                                    'goods_price', 'goods_marketprice', 'goods_salenum', 'id', 'rewards', 'is_rebate')->where('goods_state', 1)->where('is_point_activity', 0)->first();

                                //                        $goods_skus=GoodsSku::where(['goods_id' => $value,'is_rebate'=>1])->->get();
                                //                        $group_arr=array_column($goods_skus,'group_price');
                                //                        $group_price= min($group_arr);

                                if (!empty($goods_info)) {
                                    $config[$key]['options'][$key_item] = $goods_info;
                                } else {
                                    unset($config[$key]['options'][$key_item]);
                                }
                            }
                            $config[$key]['options'] = array_values($config[$key]['options']);
                        }
                    }
                }
            }

            return $config;
        });
        return $config;
    }
}