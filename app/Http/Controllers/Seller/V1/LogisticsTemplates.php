<?php
/**
 * @Filename LogisticsTemplates.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\LogisticsTemplate;
use Illuminate\Http\Request;
use ShopEM\Http\Requests\Seller\LogisticsTemplatesRequest;
use ShopEM\Repositories\LogisticsTemplatesRepository;
use ShopEM\Services\LogisticsTemplatesServices;

class LogisticsTemplates extends BaseController
{


    /**
     * 运费模板列表显示
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function lists(Request $request, LogisticsTemplatesRepository $LogisticsTemplates)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['per_page'] = config('app.per_page');

        $lists = $LogisticsTemplates->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $LogisticsTemplates->listShowFields(),
        ]);
    }


    /**
     * 新增模板和编辑模板页面显示
     *
     * @Author hfh_wind
     * @return mixed
     *
     */
    public function detailView(Request $request)
    {
        $id = $request->id;
        if (empty($id)) {
            return $this->resFailed(414, '模板id必填!');
        }

        $shop_id = $this->shop->id;
        $data = [];
        $template = LogisticsTemplate::where(['id' => $id, 'shop_id' => $shop_id])->first();

        //byweight
        if ($template->valuation == '1') {
            $data['fee_conf'] = json_decode($template->fee_conf, true);
            $data['free_conf'] = json_decode($template->free_conf, true);
        }
        //bynum
        if ($template->valuation == '2') {
            $data['fee_conf'] = json_decode($template->fee_conf, true);
            $data['free_conf'] = json_decode($template->free_conf, true);
        }
        //bymoney
        if ($template->valuation == '3') {
            $data['fee_conf'] = json_decode($template->fee_conf, true);
        }
        $data['data'] = $template;
        if(!empty($template->free_conf)){
            //是否开启免邮规则
            $data['data']['open_freerule']=1;
        }

        return $this->resSuccess($data);
    }


    /**
     * 存储店铺快递运费模板数据
     *
     * @Author hfh_wind
     * @param $data
     * @param $shopId
     * @return bool
     */
    public function addTemplates(
        LogisticsTemplatesRequest $logisticsTemplates,
        LogisticsTemplatesServices $logisticsTemplatesServices
    ) {
        $logisticsTemplates = $logisticsTemplates->only('name', 'order_sort', 'status', 'valuation', 'protect',
            'protect_rate', 'minprice', 'fee_conf', 'free_conf', 'is_free','open_freerule');
        $shopId = $this->shop->id;

        //检查数据
        $logisticsTemplatesServices->__check($logisticsTemplates, $shopId);
        //处理数据,返回需要的字段
        $saveData = $logisticsTemplatesServices->__preData($logisticsTemplates, $shopId);
//        testLog($logisticsTemplates);
//        testLog("------");
//        testLog($saveData);
        try {

            LogisticsTemplate::create($saveData);

        } catch (\Exception $e) {

            return $this->resFailed(700, $e->getMessage() . '保存失败!');

        }

        return $this->resSuccess([], '保存成功!');
    }


    /**
     * 更新快递运费模板数据
     *
     * @Author hfh_wind
     * @param $data
     * @param $shopId
     * @return bool
     */
    public function updateTemplates(
        LogisticsTemplatesRequest $logisticsTemplates,
        LogisticsTemplatesServices $logisticsTemplatesServices
    ) {
        $logisticsTemplates = $logisticsTemplates->only('id', 'name', 'order_sort', 'status', 'valuation', 'protect',
            'protect_rate', 'minprice', 'fee_conf', 'free_conf', 'is_free','open_freerule');



        if (empty($logisticsTemplates['id'])) {
            return $this->resFailed(414, '模板id必填!');
        }
        $shopId = $this->shop->id;

        $logisticsTemplatesServices->__check($logisticsTemplates, $shopId);
        $saveData = $logisticsTemplatesServices->__preData($logisticsTemplates, $shopId);

        try {

            LogisticsTemplate::where(['id' => $logisticsTemplates['id'], 'shop_id' => $shopId])->update($saveData);

        } catch (\Exception $e) {

            return $this->resFailed(700, $e->getMessage() . '保存失败!');

        }

        return $this->resSuccess([], '保存成功!');
    }


    /**
     * 删除对应ID的快递运费模板
     *
     * @Author hfh_wind
     * @param $filter
     * @return mixed
     */
    public function remove(Request $request)
    {
        $id = $request->id;
        if (empty($id)) {
            return $this->resFailed(414, '模板id必填!');
        }

        try {

            LogisticsTemplate::where(['id' => $id])->delete();

        } catch (\Exception $e) {

            return $this->resFailed(700, $e->getMessage() . '删除失败!');

        }

        return $this->resSuccess([], '删除成功!');
    }


}