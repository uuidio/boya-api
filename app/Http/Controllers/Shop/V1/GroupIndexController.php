<?php

/**
 * 甄选/牛币
 * GroupIndexController.php
 * @Author: nlx
 * @Date:   2020-03-23 10:18:15
 */
namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Repositories\SiteConfigRepository;
use ShopEM\Repositories\RecommendConfigRepository;
use ShopEM\Repositories\ConfigRepository;
use ShopEM\Repositories\GmPlatformRepository;
use ShopEM\Models\GmPlatform;


class GroupIndexController extends BaseController
{
	
	public function __construct()
	{
		parent::__construct();
		$this->selfId = GmPlatform::gmSelf();
	}
    /**
     * [getRecommend 下拉获取为你推荐]
     * @param  SiteConfigRepository $repository [description]
     * @return [type]                           [description]
     */
    public function getRecommend(Request $request,RecommendConfigRepository $repository)
    {
        $id = 0;
        if (!isset($request->title_id)) 
        {
            $title = $repository->getRecommendTitle($request->pages,$this->selfId);
            if (!empty($title)) 
            {
                $id = $title[0]['id'];
            }
        }
        $title_id = $request->title_id??$id;
        return $this->resSuccess($repository->getRecommend($request->pages,$title_id,$this->selfId));
    }
    /**
     * [getRecommend 为你推荐导航]
     * @param  SiteConfigRepository $repository [description]
     * @return [type]                           [description]
     */
    public function getRecommendTitle(Request $request,RecommendConfigRepository $repository)
    {
        return $this->resSuccess($repository->getRecommendTitle($request->pages,$this->selfId));
    }


    /**
     * [newIndexWidgets 首页挂件升级-甄选]
     * @param  Request          $request    [description]
     * @param  ConfigRepository $repository [description]
     * @return [type]                       [description]
     */
    public function newIndexWidgets(Request $request,ConfigRepository $repository)
    {
        if(!isset($request->page)){
            return $this->resFailed(500,'参数错误');
        }
        $page = $request->page;
        return $this->resSuccess($repository->configItems_index($request->page,$this->selfId));
    }


    /**
     * [platformPointList description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function platformPointList(Request $request,GmPlatformRepository $repository)
    {
        $input['open_point_exchange'] = 1;
        $data = $repository->normalLists($input);
        return $this->resSuccess($data);
    }

}