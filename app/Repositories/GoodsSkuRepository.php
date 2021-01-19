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
use Illuminate\Support\Facades\DB;
use ShopEM\Models\GoodsSku;

class GoodsSkuRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'keyword' => ['field' => 'goods_skus.goods_name', 'operator' => 'like'],
        'shop_id' => ['field' => 'goods_skus.shop_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'spec_sign', 'title' => '规格属性'],
            ['dataIndex' => 'goods_image', 'title' => '商品主图','scopedSlots'=>['customRender'=>'goods_image']],
            ['dataIndex' => 'goods_price', 'title' => '商品价格'],
            ['dataIndex' => 'goods_marketprice', 'title' => '市场价'],
            ['dataIndex' => 'goods_serial', 'title' => '商品货号'],
            ['dataIndex' => 'goods_stock', 'title' => '商品库存'],
            ['dataIndex' => 'created_at', 'title' => '发布时间'],
            ['dataIndex' => 'goods_tab', 'title' => '活动标签'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $shop_id
     * @return mixed
     */
    public function listItems($data)
    {
        $goodsModel = new GoodsSku();
        $goodsModel = filterModel($goodsModel, $this->filterables, $data);

        $goodsModel->leftJoin('goods','goods.id', '=', 'goods_skus.goods_id')->where('goods.goods_state','=','1')->select('goods_skus.*');

        if (isset($data['not_in_id']) && $data['not_in_id']) {
            $goodsModel = $goodsModel->whereNotIn('goods_skus.id', $data['not_in_id']);
        }

        return $goodsModel->orderBy('id', 'desc')->paginate(config('app.per_page'));
    }

}