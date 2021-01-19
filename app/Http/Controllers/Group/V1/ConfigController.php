<?php
/**
 * @Filename        SiteConfigController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\Config;
use ShopEM\Repositories\ConfigRepository;

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
//        page = 'group'  group = 'platform_attrs'
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

        $hasConfig = Config::where('gm_id',0)->where('page', $data['page'])->where('group', $data['group'])->first();
        if (empty($hasConfig)) {
            $data['gm_id'] = 0;
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
        return $this->resSuccess($repository->configItems($request->page, $request->group));
    }

}
