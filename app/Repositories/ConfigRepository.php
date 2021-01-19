<?php
/**
 * @Filename        SiteConfigRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\Config;
use ShopEM\Models\Goods;
use ShopEM\Models\SecKillApplie;
use Illuminate\Support\Facades\Cache;

class ConfigRepository
{
    /**
     * 获取站点配置
     *
     * @Author moocde <mo@mocode.cn>
     * @param $page
     * @return mixed
     */
    public function configItems($page, $group,$gm_id=0)
    {
        $config = [];
        $model = new Config();

        $model = $model::where('page', $page)->where('group', $group);
        if ($gm_id>0) 
        {
            $model = $model->where('gm_id',$gm_id);
        }
        $items = $model->first();
        if($items) {
            //修改站点配置的数据格式
            $config_info = json_decode($items['value'], true);
            if($group!='free_order_amount'){
                foreach ($config_info as $k => $v) {
                    $v['key'] = $k;
                    $config[] = $v;
                }
            }else{
                $config=  $config_info;
            }

        }

        return $config;
    }
    /**
     * 获取单个配置
     *
     * @Author moocde <mo@mocode.cn>
     * @param $page
     * @return mixed
     */
    public function configItem($page, $group ,$gm_id=0)
    {
        $config = [];
        
        $model = new Config();
        $model = $model::where('page', $page)->where('group', $group);
        if ($gm_id>0) 
        {
            $model = $model->where('gm_id',$gm_id);
        }
        $item = $model->first();

        if ($item) {
            $config = json_decode($item['value'], true);
        }

        return $config;
    }

    /**
     * 首页装修
     * @Author hfh_wind
     * @param $page
     * @return array
     */
    public function configItems_index($page,$gm_id=0,$cache=true)
    {
        $cache_key = 'CONFIGITEMS_INDEX_PAGE_'.$page.'_GM_'.$gm_id;
        if (Cache::has($cache_key) && $cache) {
            $configCache = Cache::get($cache_key);
            if (!empty($configCache)) {
                return $configCache;
            }
            Cache::forget($cache_key);
        }

        $config = [];
        $items = Config::where('page', $page);
        if($gm_id>0){
            $items = $items->where('gm_id',$gm_id);
        }
        $items = $items->get();
        $this->newConfigItems($items,$gm_id,$config);
        
        Cache::forget($cache_key);
        Cache::put($cache_key, $config, now()->addMinutes(60));
        // Cache::forever($cache_key, $config);
        return $config;
    }



    /**
     * [configItems_make 处理数据]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function MakeConfigItems($data)
    {
        if ($data['id'] == 'ActivityGroup') 
        {
            foreach ($data['options'] as $key => &$value) 
            {
                // if (!isset($value['main_title']) || empty($value['main_title'])) {
                //     throw new \Exception("请完善活动标题");
                // }
                
                if (!empty($value['goodsList'])) {
                    $value['goodsList'] = array_column($value['goodsList'], 'id');
                }
            }
        }

        if ($data['id'] == 'Special') 
        {
            if (!empty($data['options'])) {
                $data['options'] = array_column($data['options'], 'id');
            }
        }
        return $data;
    }


    /**
     * [newConfigItems 新的装修挂件统一处理（自定义+首页）]
     * @param  array  $items   [description]
     * @param  [type] &$config [description]
     * @return [type]          [description]
     */
    public function newConfigItems($items=[],$gm_id=0,&$config)
    {
        $use_array_values = false;
        foreach ($items as $key => $item) 
        {
            $config[$key] = json_decode($item['value'],true);
            $config[$key]['site_id'] = $item['id'];
            $config[$key]['gm_id'] = $item['gm_id'];

            if ($item['group'] == 'goodGroup' || $item['group']=='GoodSwiper') 
            {
                $use_array_values = true;
                foreach ($config[$key]['options'] as $key_item => $value) 
                {
                    $goods_info = Goods::where(['id' => $value])->select('goods_image', 'goods_name',
                        'goods_price', 'goods_marketprice', 'goods_salenum', 'id')->where('goods_state', 1)->first();

                    if (!empty($goods_info)) {
                        if ($goods_info->web_show_price > 0) {
                            $goods_info->goods_price = $goods_info->web_show_price;
                        }
                        $config[$key]['options'][$key_item] = $goods_info;
                    } else {

                        unset($config[$key]['options'][$key_item]);
                    }
                }
            }
            if ($item['group'] == 'ActivityGroup') 
            {
                $use_array_values = true;
                foreach ($config[$key]['options'] as $key_item => $value) 
                {
                    $group_info = [];
                    if (!is_array($value)) {
                        continue;
                    }
                    $group_info = $value;
                    //赋值
                    $group_info = $value;
                    $group_info['showTime'] = false;

                    $goodsList = Goods::whereIn('id',$value['goodsList'])
                                ->select('goods_image', 'goods_name','goods_price', 'goods_marketprice', 'goods_salenum', 'id')
                                ->get()->toArray();
                    foreach ($goodsList as $gkey => $goods) {
                        if (isset($goods['web_show_price']) && $goods['web_show_price'] > 0) {
                            $goodsList[$gkey]['goods_price'] = $goods['web_show_price'];
                        }
                    }
                    //赋值
                    $group_info['goodsList'] = $goodsList;
                    if (isset($value['countDown']) && $value['countDown'] && $value['value'] == 'seckill') 
                    {
                        $nowTime = date('Y-m-d H:i:s', time());
                        $model = new SecKillApplie;
                        if ($gm_id > 0 ) 
                        {
                            $model = $model->where('gm_id', '=', $gm_id);
                        }
                        $model = $model->where('end_time', '>=', $nowTime)->where('start_time','<',$nowTime);
                        $seckill = $model->latest('start_time')->first();
                        if (!empty($seckill)) 
                        {
                            //赋值
                            $group_info['showTime'] = true;
                            $group_info['seckill_start_time'] = $seckill->start_time;
                            $group_info['seckill_end_time'] = $seckill->end_time;
                            $group_info['seckill_remain_time'] = strtotime($seckill->end_time)-time();
                        }
                    }
                    $config[$key]['options'][$key_item] = $group_info;
                }

            }
            if ($item['group'] == 'Special') 
            {
                $use_array_values = true;
                foreach ($config[$key]['options'] as $key_item => $value) 
                {
                    if (isset($value['goods_name'])) {
                        continue;
                    }
                    $goods_info = Goods::where(['id' => $value])->select('goods_image', 'goods_name',
                        'goods_price', 'goods_marketprice', 'goods_salenum', 'id')->where('goods_state', 1)->first();

                    if (!empty($goods_info)) {
                        if ($goods_info->web_show_price > 0) {
                            $goods_info->goods_price = $goods_info->web_show_price;
                        }
                        $config[$key]['options'][$key_item] = $goods_info;
                    } else {
                        unset($config[$key]['options'][$key_item]);
                    }
                }

            }
            //重新定义排序
            if ($use_array_values) $config[$key]['options'] = array_values($config[$key]['options']);
            $use_array_values = false;

        }
        if (!empty($items)) {
            $last_names = array_column($items->toArray(),'listorder');
            array_multisort($last_names,SORT_ASC,$config);
        }
    }
}