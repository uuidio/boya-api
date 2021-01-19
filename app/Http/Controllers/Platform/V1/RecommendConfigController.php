<?php

/**
 * RecommendConfigController.php
 * @Author: nlx
 * @Date:   2020-03-20 09:41:30
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-06-15 17:21:30
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\RecommendConfig;
use ShopEM\Models\Goods;
use Illuminate\Support\Facades\DB;
use ShopEM\Repositories\RecommendConfigRepository;

class RecommendConfigController extends BaseController
{

    /**
     * 添加站点挂件
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->only('page', 'group', 'value');
        $data['value'] = empty($data['value']) ? [] : $data['value'];

        if (empty($data['page']) || empty($data['group'])) {
            return $this->resFailed(702, '提交参数错误');
        }

        $content = $data['value'];

        DB::beginTransaction();
        try {
            foreach ($content as $key => $value) 
            {
            	if (empty($value['title'])) 
            	{
            		return $this->resFailed(702, '第'.($key+1).'的 标题必填');
            	}
            }
            foreach ($content as $key => $value) 
            {
                $data['value'] = json_encode($value);
                $data['title'] = $value['title'];
                $data['gm_id'] = $this->GMID;
                $data['listorder'] = $key;
                
                if (isset($value['site_id'])) {
                    $site_id = $value['site_id'];
                    $filter = [
                        'id'    => $site_id,
                        'page' => $data['page'],
                        'gm_id' => $this->GMID,
                    ];
                    $hasConfig = RecommendConfig::where($filter)->first();
                    if (!empty($hasConfig)) {
                        $hasConfig->update($data);
                    }else{
                        //如果有id但找不到的情况下,创建
                        $newSite = RecommendConfig::create($this->valueData($data));
                        $site_id = $newSite['id'];
                    }

                } else {
                    //属于新建的
                    $newSite = RecommendConfig::create($this->valueData($data));
                    $site_id = $newSite['id'];
                }
                $ids[$key] = $site_id;
            }
            RecommendConfig::where('page', $data['page'])->where('gm_id',$this->GMID)->whereNotIn('id', $ids)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = $e->getMessage();

            throw new \logicexception($msg);
        }

        return $this->resSuccess();
    }

    //判断value是否有商品
    public function valueData($data)
    {
        $value = json_decode($data['value'],1);
        $goods_ids = $value['value'];
        if (empty($goods_ids)) 
        {
            $goods_ids = [];
            $goods = DB::table('goods')
                    ->where(['goods_state'=>1,'gm_id'=>$this->GMID])
                    ->where('is_point_activity', 0)
                    ->orderBy(DB::raw('RAND()'))
                    ->take(30)
                    ->pluck('id');
            foreach ($goods as $id) {
                $goods_ids[] = $id;
            }
            $value['value'] = $goods_ids;
            $data['value'] = json_encode($value);
        }
        return $data;
    }


    public function items(Request $request, RecommendConfigRepository $repository)
    {
    	return $this->resSuccess($repository->configItems($request->page,$this->GMID,$request->site_id??0));
    }


}