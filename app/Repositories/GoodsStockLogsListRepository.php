<?php
/**
 * @Filename        GoodsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Http\Request;
use ShopEM\Models\Goods;
use ShopEM\Models\GoodsStockLog;

class GoodsStockLogsListRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'id'       => ['field' => 'id', 'operator' => '='],
        'shop_id'  => ['field' => 'shop_id', 'operator' => '='],
        'goods_id' => ['field' => 'goods_id', 'operator' => '='],
        'goods_name' => ['field' => 'goods_name', 'operator' => 'like'],
        'sku_id'   => ['field' => 'sku_id', 'operator' => '='],
        'type'     => ['field' => 'type', 'operator' => '='],
        'oid'      => ['field' => 'oid', 'operator' => '='],
        'gm_id'      => ['field' => 'gm_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'sku_id', 'title' => 'sku_id'],
            ['dataIndex' => 'goods_stock', 'title' => '实时库存'],
            ['dataIndex' => 'change', 'title' => '修改值'],
            ['dataIndex' => 'type_text', 'title' => '类型'],
            ['dataIndex' => 'oid', 'title' => '子订单'],
            ['dataIndex' => 'note', 'title' => '描述'],
            ['dataIndex' => 'created_at', 'title' => '记录时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     * @Author hfh_wind
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表数据
     * @Author hfh_wind
     * @param $data
     * @param bool $recycle
     * @return mixed
     */
    public function listItems($data)
    {
        $model = new GoodsStockLog();

        if (isset($data['goods_name']) && $data['goods_name']) {
            $goods_name = $data['goods_name'];
            $good_info=Goods::where('goods_name', 'like', '%' . $goods_name . '%')->first();
            $data['goods_id']=$good_info['id'];
            unset($data['goods_name']);
        }

        $model = filterModel($model, $this->filterables, $data);

        return $model->orderBy('id', 'desc')->paginate(config('app.per_page'));
    }


}