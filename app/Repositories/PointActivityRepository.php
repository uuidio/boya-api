<?php
/**
 * @Filename PointActivityRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\PointActivityGoods;
use Carbon\Carbon;

class PointActivityRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'gm_id'         => ['field' => 'point_activity_goods.gm_id', 'operator' => '='],
        'id'         => ['field' => 'point_activity_goods.id', 'operator' => '='],
        'good_name'  => ['field' => 'point_activity_goods.good_name', 'operator' => '='],
        'shop_id'    => ['field' => 'point_activity_goods.shop_id', 'operator' => '='],
        'goods_id'   => ['field' => 'point_activity_goods.goods_id', 'operator' => '='],
        'sku_id'     => ['field' => 'point_activity_goods.sku_id', 'operator' => '='],
        'created_at' => ['field' => 'point_activity_goods.created_at', 'operator' => '='],
        'class_id' => ['field' =>   'point_activity_goods.point_class_id', 'operator' => '='],
        
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
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'goods_price', 'title' => '店铺价格'],
            ['dataIndex' => 'point_price', 'title' => '活动价格'],
            ['dataIndex' => 'point_fee', 'title' => '积分','hide'=>isshow_models($is_show,['normal'])],
            ['dataIndex' => 'point_fee', 'title' => '牛币','hide'=>isshow_models($is_show,['self'])],
            ['dataIndex' => 'goods_image', 'title' => '商品图片'],
            ['dataIndex' => 'good_stock', 'title' => '剩余库存'],
            ['dataIndex' => 'exchange', 'title' => '已兑换'],
            ['dataIndex' => 'buy_max', 'title' => '每人总限购'],
            ['dataIndex' => 'day_buy_max', 'title' => '每人每日限购'],
            ['dataIndex' => 'grade_text', 'title' => '会员等级'],
            ['dataIndex' => 'active_status_name', 'title' => '兑换状态'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
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
     * 商品查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request, $role = 'platform')
    {
        $model = new PointActivityGoods();
        $orderby = 'point_activity_goods.point_fee';
        $direction = 'desc';
        if (isset($request['orderby']) ) 
        {
            $orderby = $request['orderby'];
            unset($request['orderby']);
        }
        if (isset($request['direction']) && in_array($request['direction'], ['desc', 'asc'])) 
        {
            $direction = $request['direction'];
            unset($request['direction']);
        }

        
        $model = filterModel($model, $this->filterables, $request);
        $time = Carbon::now()->toDateTimeString();
        if ($role == 'shop') {
            $model = $model->leftJoin('goods', 'goods.id', '=', 'point_activity_goods.goods_id')->where('goods_state', 1);
            $model = $model->where(function ($query) use ($time){
                $query->whereNull('active_end')
                      ->orWhere('active_end', '>', $time);
            });
        }
        $lists = $model->select('point_activity_goods.*')->orderBy($orderby, $direction)->orderBy('point_activity_goods.sort', 'desc')->paginate($request['per_page']);

        return $lists;
    }

}