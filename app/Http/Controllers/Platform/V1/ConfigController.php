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
use ShopEM\Models\Config;
use ShopEM\Repositories\ConfigRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ConfigController extends BaseController
{
    /**
     * 添加站点配置
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

        //修改站点配置的数据格式
        $configs = $data['value'];
        $config = [];
        if ($data['group'] != 'free_order_amount') {
            foreach ($configs as $v) {

                if (isset($v['type']) && $v['type'] == 'number') {
                    if (!is_numeric($v['value'])) {
                        return $this->resFailed(702, $v['name'] . '必须为数字');
                    }
                    $v['value'] = floatval($v['value']);
                } elseif (isset($v['type']) && $v['type'] == 'switch') {
                    if (!in_array($v['value'], [0, 1])) {
                        return $this->resFailed(702, $v['name'] . '参数错误');
                    }
                    $v['value'] = intval($v['value']);
                }
                $key = $v['key'];
                unset($v['key']);
                $config[$key] = $v;
            }
        }else{
            $config = $data['value'];
        }

        $data['value'] = json_encode($config);

        $hasConfig = Config::where('gm_id',$this->GMID)->where('page', $data['page'])->where('group', $data['group'])->first();
        if (empty($hasConfig)) {
            $data['gm_id'] = $this->GMID;
            Config::create($data);
        } else {
            $hasConfig->update($data);
        }

        return $this->resSuccess();
    }

    /**
     * 获取站点配置
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param ConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function items(Request $request, ConfigRepository $repository)
    {
        return $this->resSuccess($repository->configItems($request->page, $request->group, $this->GMID));
    }


    /**
     * 添加首页装修
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store_index(Request $request, ConfigRepository $repository)
    {
        $data = $request->only('page', 'group', 'value');

        $data['value'] = empty($data['value']) ? [] : $data['value'];

        if (empty($data['page'])) {
            return $this->resFailed(702, '提交参数错误');
        }

//        $content =json_decode($data['value'],true);
        $content = $data['value'];

        //删除首页挂件缓存
        $cache_key = 'CONFIGITEMS_INDEX_PAGE_'.$data['page'].'_GM_'.$this->GMID;
        Cache::forget($cache_key);

        $admin_log = '首页装修';
        $ids = [];
        DB::beginTransaction();
        try {
            // $this->testConfigLog(['gm_id'=>$this->GMID,'content'=>$content]);
            foreach ($content as $key => $value) 
            {
                $value = $repository->MakeConfigItems($value);
                // $data['value'] = $value;
                $data['value'] = json_encode($value);
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
                    $hasConfig = Config::where($filter)->first();

                    if (!empty($hasConfig)) {
                        $hasConfig->update($data);
                    }else{
                        //如果有id但找不到的情况下,创建
                        $newSite = Config::create($data);
                        $site_id = $newSite['id'];
                    }

                } else {
                    //属于新建的
                    $newSite = Config::create($data);
                    $site_id = $newSite['id'];
                }
                $ids[$key] = $site_id;
            }
            Config::where('page', $data['page'])->where('gm_id',$this->GMID)->whereNotIn('id', $ids)->delete();

            
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

        return $this->resSuccess();
    }


    /**
     * 首页装修
     * @Author hfh_wind
     * @param Request $request
     * @param SiteConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function items_index(Request $request, ConfigRepository $repository)
    {
        return $this->resSuccess($repository->configItems_index($request->page,$this->GMID,false));
    }

    /**
     * [FunctionName description]
     * @param string $value [description]
     */
    public function addNewConfig(Request $request)
    {
        $data = $request->only('page', 'group');
        $baseconfig = config('baseconfig');
        $hasConfig = Config::where('page', $data['page'])->where('group', $data['group'])->get()->toArray();
        // dd( $hasConfig );
        foreach ($baseconfig as $key => $value) 
        {
            if ($value['page'] == $data['page'] && $value['group'] == $data['group']) 
            {
                $config = json_decode($value['value'], true);
            }
        }

        
        foreach ($hasConfig as $vkey => $value) 
        {
            $data = json_decode($value['value'], true);
            $save = [];
            foreach ($config as $key => $val) 
            {
                $save[$key] = isset($data[$key])?$data[$key]:$config[$key];
            }
            Config::where('id', $value['id'])->update(['value'=>json_encode($save)]);
        }
        echo "succ";

    }

    /**
     * 测试日志记录
     *
     * @Author moocde <mo@mocode.cn>
     * @param $info
     */
    public function testConfigLog($info)
    {
        $filename = storage_path('logs/' . 'config-log-' . date('Y-m-d') . '.log');
        file_put_contents($filename, '[' . date('Y-m-d H:i:s') . '] ' . print_r($info, true) . "\n", FILE_APPEND);
    }
}