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
use ShopEM\Models\RecommendConfig;

class RecommendConfigRepository
{

    /**
     * 获取挂件配置
     * @Author hfh_wind
     * @param $page
     * @return array
     */
    public function configItems($page,$gm_id=0,$title_id=0)
    {
        $config = [];
        $items = RecommendConfig::where('page', $page);
        if($gm_id>0){
            $items = $items->where('gm_id',$gm_id);
        }
        if ($title_id>0) 
        {
            $items = $items->where('id',$title_id);
        }
        $items = $items->get();

        foreach ($items as $key => $item) 
        {
            $data = json_decode($item['value'],true)['value'];
            $config[$key]['site_id'] = $item['id'];
            $config[$key]['title'] = $item['title'];
            $config[$key]['count'] = count($data);
            $config[$key]['value'] = [];
            if ($item['group'] == 'recommend') 
            {
                $options = [];
                foreach ($data as $key_item => $value) 
                {
                    $goods_info = Goods::where(['id' => $value,'goods_state'=>1])->select('goods_image', 'goods_name',
                        'goods_price', 'goods_marketprice', 'goods_salenum', 'id')->first();
                    if (!empty($goods_info)) 
                    {
                        $options[]  = $goods_info;
                    }
                }
                $config[$key]['value'] = $options;
            }
        }
        if (!empty($items)) {
            $last_names = array_column($items->toArray(),'listorder');
            array_multisort($last_names,SORT_ASC,$config);
        }
        return $config;
    }


    /**
     * 下拉获取为你推荐
     * @Author huiho
     * @return mixed
     */
    public function getRecommend($page,$title_id,$gm_id=0)
    {
        $items = RecommendConfig::where('group', 'recommend')->where('id', $title_id)->where('page', $page);
        if($gm_id>0){
            $items = $items->where('gm_id',$gm_id);
        }
        $items = $items->get();
        if (count($items)<=0) return Goods::where('id','-1')->paginate(config('app.per_page'));
                        

        $config = json_decode($items[0]['value'],true);
        $itemIds = $config['value'];
        $lists = Goods::select('goods_image', 'goods_name',
                        'goods_price', 'goods_marketprice', 'goods_salenum', 'id')
                        ->whereIn('id' , $itemIds)
                        ->where('goods_state', 1)
                        ->where('is_point_activity', 0)
                        ->orderBy('id', 'desc')
                        ->paginate(config('app.per_page'));

         // 加上商品活动和促销信息
        $lists = $lists->toArray();
        $service = new \ShopEM\Repositories\GoodsRepository;
        foreach ($lists['data'] as $key => &$value) {
            $value['good_sign'] = $service->goodConnectInfo($value['id']);
            // $value['good_sign'] = [];
        }
        
        return $lists;
    }


    public function getRecommendTitle($page,$gm_id=0)
    {
        $items = RecommendConfig::where('group', 'recommend')->where('page', $page);
        if($gm_id>0){
            $items = $items->where('gm_id',$gm_id);
        }
        $items = $items->select('id','title','listorder')->get()->toArray();
        if (!empty($items)) {
            $last_names = array_column($items,'listorder');
            array_multisort($last_names,SORT_ASC,$items);
        }
        return $items;
    }


}