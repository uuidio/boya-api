<?php
/**
 * @Filename TradeAfterSalesRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\TradeAftersales;

class TradeAfterSalesRepository
{

    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'id' => ['field' => 'id', 'operator' => '='],
        'aftersales_bn' => ['field' => 'aftersales_bn', 'operator' => '='],
        'tid' => ['field' => 'tid', 'operator' => '='],
        'oid' => ['field' => 'oid', 'operator' => '='],
        'user_id' => ['field' => 'user_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'aftersales_type' => ['field' => 'aftersales_type', 'operator' => '='],
        'progress' => ['field' => 'progress', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
        'created_start'  => ['field' => 'created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'created_at', 'operator' => '<='],
    ];

    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields($is_show='')
    {
        return [
            ['dataIndex' => 'aftersales_bn', 'title' => '申请售后编号'],
            ['dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            ['dataIndex' => 'shop_name', 'title' => '所属商家'],
            ['dataIndex' => 'user_name', 'title' => '会员'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'oid', 'title' => '子订单号'],
            ['dataIndex' => 'aftersales_type_text', 'title' => '售后服务类型'],
            ['dataIndex' => 'progress_text', 'title' => '处理进度'],
            ['dataIndex' => 'status_text', 'title' => '状态'],
            ['dataIndex' => 'title', 'title' => '商品标题'],
            ['dataIndex' => 'num', 'title' => '申请售后商品数量'],
            ['dataIndex' => 'reason', 'title' => '申请售后原因'],
            ['dataIndex' => 'description', 'title' => '申请描述'],
            ['dataIndex' => 'shop_explanation', 'title' => '商家处理申请说明'],
            ['dataIndex' => 'admin_explanation', 'title' => '平台处理申请说明'],
            ['dataIndex' => 'refunds_reason', 'title' => '商家退款备注'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
            ['dataIndex' => 'updated_at', 'title' => '最后操作时间'],
        ];
    }


    /**
     * 退货列表查询字段
     *
     * @Author Huiho
     * @return array
     */
    public function refundGoodsFields($is_show='')
    {
//        return [
//            ['dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
//            ['dataIndex' => 'order_date_at', 'title' => '下单日期'],
//            ['dataIndex' => 'shop_name', 'title' => '所属商家'],
//            ['dataIndex' => 'user_name', 'title' => '会员'],
//            ['dataIndex' => 'tid', 'title' => '订单号'],
//            ['dataIndex' => 'oid', 'title' => '子订单号'],
//            ['dataIndex' => 'progress_text', 'title' => '处理进度'],
//            ['dataIndex' => 'status_text', 'title' => '状态'],
//            ['dataIndex' => 'title', 'title' => '商品标题'],
//            ['dataIndex' => 'num', 'title' => '申请售后商品数量'],
//            ['dataIndex' => 'reason', 'title' => '申请售后原因'],
//            ['dataIndex' => 'description', 'title' => '申请描述'],
//            ['dataIndex' => 'shop_explanation', 'title' => '商家处理申请说明'],
//            ['dataIndex' => 'admin_explanation', 'title' => '平台处理申请说明'],
//            ['dataIndex' => 'refunds_reason', 'title' => '商家退款备注'],
//            ['dataIndex' => 'created_at', 'title' => '创建时间'],
//            ['dataIndex' => 'refund_at', 'title' => '退款时间'],
//        ];

        return [
            ['dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            ['dataIndex' => 'order_date_at', 'title' => '下单日期'],
            ['dataIndex' => 'shop_name', 'title' => '所属商家'],
            ['dataIndex' => 'aftersales_bn', 'title' => '申请售后编号'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'oid', 'title' => '子订单号'],
            ['dataIndex' => 'progress_text', 'title' => '处理进度'],
            ['dataIndex' => 'num', 'title' => '申请售后商品数量'],
            ['dataIndex' => 'reason', 'title' => '退货原因'],
            ['dataIndex' => 'refund_at', 'title' => '退货时间'],
            ['dataIndex' => 'refund_at', 'title' => '退款时间'],
        ];
    }




    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     *
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 订单查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request,$downData='')
    {
        if (isset($request['shop_name']) && $request['shop_name']) {
            $shop = \ShopEM\Models\Shop::where('shop_name',$request['shop_name'])->first();
            if (!$shop) {
                throw new \Exception('找不到该店铺!');
            }
            $request['shop_id'] = $shop->id;
        }
        $model = new TradeAftersales();
        $model = filterModel($model, $this->filterables, $request);
        if($downData){
            //下载提供数据
            $lists=$model->orderBy('id', 'desc')->get();
        }else{
            $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);
        }

        return $lists;
    }

}