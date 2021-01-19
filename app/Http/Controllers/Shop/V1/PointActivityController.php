<?php
/**
 * @Filename        PointActivityController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Repositories\PointActivityRepository;
use ShopEM\Repositories\PointGoodsClassRepository;
use ShopEM\Models\GmPlatform;



class PointActivityController extends BaseController
{

    public static function useGmId($request)
    {
        if (strpos($request->path(),'-group') !== false) 
        {
            return GmPlatform::gmSelf();
        }
        return false;
    }
    /**
     * 积分中心页面
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, PointActivityRepository $repository)
    {
        
        $data = $request->all();
        if (!isset($data['gm_id'])) 
        {
            $data['gm_id'] = $this->GMID;
        }
        if ($gmid = self::useGmId($request)) 
        {
            $data['gm_id'] = $gmid;
        }
        $data['per_page'] = isset($data['per_page']) ? $data['per_page'] : config('app.per_page');
        $lists = $repository->search($data, 'shop');

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'banner_images' => 'https://yt-ego-oss.oss-cn-shenzhen.aliyuncs.com/images/default/201910/30/QshgsGfuestcmMKGZtcWz1gxXGXLi8KKZQ0h2oOi.jpeg',
        ]);
    }

    /**
    * 积分分类列表
    * @Author swl
    */
    public function classLists(Request $request, PointGoodsClassRepository $repository)
    {   
        $data = $request->all();
        if (!isset($data['gm_id'])) 
        {
            $data['gm_id'] = $this->GMID;
        }
        if ($gmid = self::useGmId($request)) 
        {
           $data['gm_id'] = $gmid;
        }
       

        $lists = $repository->allListItems($data);
        if (empty($lists)) {
            return $this->resFailed(700,'暂无数据');
        }

        return $this->resSuccess([
            'lists' => $lists,
        ]);

    }


}