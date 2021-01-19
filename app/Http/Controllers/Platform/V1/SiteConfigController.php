<?php
/**
 * @Filename        SiteConfigController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\SiteConfig;
use ShopEM\Models\CustomActivityConfig;
use Illuminate\Support\Facades\DB;
use ShopEM\Repositories\SiteConfigRepository;
use ShopEM\Repositories\ConfigRepository;
use ShopEM\Repositories\CustomActivityConfigRepository;
use Illuminate\Support\Facades\Cache;

class SiteConfigController extends BaseController
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

        $data['value'] = serialize($data['value']);

        $hasConfig = SiteConfig::where('page', $data['page'])->where('gm_id', $this->GMID)->where('group', $data['group'])->first();
        if (empty($hasConfig)) {
            $data['gm_id'] = $this->GMID;
            SiteConfig::create($data);
        } else {
            $hasConfig->update($data);
        }

        return $this->resSuccess();
    }

    /**
     * 获取站点挂件
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param SiteConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function items(Request $request, SiteConfigRepository $repository)
    {
        return $this->resSuccess($repository->configItems($request->page,$this->GMID));
    }


    /**
     * 添加站点挂件
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store_v1(Request $request,ConfigRepository $repository)
    {
        $data = $request->only('page', 'group', 'value', 'custom_id');

        $data['value'] = empty($data['value']) ? [] : $data['value'];

        if (empty($data['page'])) {
            return $this->resFailed(702, '提交参数错误');
        }

//        $content =json_decode($data['value'],true);
        $content = $data['value'];

        $ids = [];
        DB::beginTransaction();
        try {
            if ($content[0]['id'] != 'headerTitle' || empty($content[0]['title'])) 
            {
                return $this->resFailed(702, '页面标题必填');
            }
            $title = $content[0]['title'];
            $admin_log = '自定义活动-'.$title;
            if (isset($data['custom_id'])) 
            {
                CustomActivityConfig::where('id',$data['custom_id'])->update(['title'=>$content[0]['title']]);
                $admin_log .= ' ，进行更新'; 
            }
            else
            {
                $admin_log .= ' ，进行添加'; 
                $customData['title'] = $title;
                $customData['gm_id'] = $this->GMID;
                $custom = CustomActivityConfig::create($customData);
            }
            $data['custom_id'] = $data['custom_id']??$custom->id;

            foreach ($content as $key => $value) 
            {
                $value = $repository->MakeConfigItems($value);
                // $data['value'] = $value;
                $data['value'] =json_encode($value);
                $data['group'] = $value['id'];
                $data['gm_id'] = $this->GMID;
                $data['listorder'] = $key;
                
                if (isset($value['site_id'])) {
                    $site_id = $value['site_id'];
                    $filter = [
                        'id'    => $site_id,
                        'group' => $value['id'],
                        'gm_id' => $this->GMID,
                    ];
                    $hasConfig = SiteConfig::where($filter)->first();
                    if (!empty($hasConfig)) {
                        $hasConfig->update($data);
                    }else{
                        //如果有id但找不到的情况下,创建
                        $newSite = SiteConfig::create($data);
                        $site_id = $newSite['id'];
                    }

                } else {
                    //属于新建的
                    $newSite = SiteConfig::create($data);
                    $site_id = $newSite['id'];
                }
                $ids[$key] = $site_id;
            }

            SiteConfig::where('page', $data['page'])->where('gm_id',$this->GMID)->where('custom_id',$data['custom_id'])->whereNotIn('id', $ids)->delete();

            //删除自定义活动缓存
            $cache_key = 'CONFIGITEMS_V1_PAGE_'.$data['page'].'_CUSTOM_'.$data['custom_id'].'_GM_'.$this->GMID;
            Cache::forget($cache_key);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = $e->getMessage();
            //日志
            $this->adminlog($admin_log, 0);

            throw new \logicexception($msg);
        }
        //日志
        $this->adminlog($admin_log, 1);
        $res['custom_id'] = $data['custom_id'];
        return $this->resSuccess($res);
    }


    /**
     * 获取站点挂件
     * @Author hfh_wind
     * @param Request $request
     * @param SiteConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function items_v1(Request $request, SiteConfigRepository $repository)
    {
        $custom_id = $request->custom_id??0;
        return $this->resSuccess($repository->configItems_v1($request->page,$custom_id,$this->GMID));
    }

    public function show_v1(Request $request)
    {
        $field = [
            ['dataIndex' => 'id',  'title' => 'ID'],
            ['dataIndex' => 'page', 'title' => '所属页面'],
            ['dataIndex' => 'group',  'title' => '配置分组'],
//            ['dataIndex' => 'value', 'title' => '配置内容'],
        ];

        $res = SiteConfig::where('gm_id',$this->GMID)->select('id','page','group','value')->get()->toArray();
        $arr = [];
        if ($res) {
            foreach ($res as $k=>$v) {
                if (!isset($arr[$v['page']])) {
                    $arr[$v['page']] = $v;
                    $arr[$v['page']]['title'] = $v['value'];
                }
            }
        }
        $arr = array_values($arr);
        return $this->resSuccess([
            'field' => $field,
            'data'  => $arr,
        ]);
    }


    public function customActivityList(Request $request, CustomActivityConfigRepository $repository)
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;
        
        $lists = $repository->listItems($input_data, 10);
        if (empty($lists)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * [customActivityDelete 删除自定义活动]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function customActivityDelete(Request $request)
    {
        $id = $request->id??0;
        if ($id<=0) {
            return $this->resFailed(704);
        }
        
        try {
            //删除自定义活动缓存
            $page = 'custom_activity';
            $cache_key = 'CONFIGITEMS_V1_PAGE_'.$page.'_CUSTOM_'.$id.'_GM_'.$this->GMID;
            Cache::forget($cache_key);

            $config = CustomActivityConfig::where('id',intval($request->id))->where('gm_id',$this->GMID)->first();
            if (empty($config)) {
                return $this->resFailed(700, '删除的数据不存在');
            }
            $config->delete();
            SiteConfig::where('page', $page)->where('gm_id',$this->GMID)->where('id',intval($request->id))->delete();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            return $this->resFailed(600,$exception->getMessage());
        }
        
    }


}
