<?php
/**
 * @Filename ShopRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\Shop;
use Illuminate\Support\Facades\Log;

class ShopRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'keyword' => ['field' => 'shop_name', 'operator' => 'like'],
        'shop_type' => ['field' => 'shop_type', 'operator' => '='],
        'shop_state' => ['field' => 'shop_state', 'operator' => '='],
        'id' => ['field' => 'id', 'operator' => '='],
        'floors_id' => ['field' => 'floors_id', 'operator' => '='],
        'floors_id_arr' => ['field' => 'floors_id', 'operator' => 'in'],
        'gm_id' => ['field' => 'shops.gm_id', 'operator' => '='],
        'store_code' => ['field' => 'store_code', 'operator' => '='],
//        'rel_cat_id'    => ['field' => 'rel_cat_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields($is_show = '')
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'gm_name', 'dataIndex' => 'gm_name', 'title' => '所属项目', 'hide' => isshow_models($is_show, ['group'])],
            ['key' => 'shop_name', 'dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['key' => 'shopRate', 'dataIndex' => 'shopRate', 'title' => '店铺扣点（%）'],
            ['key' => 'shop_goods_sum', 'dataIndex' => 'shop_goods_sum', 'title' => '店铺上架商品总量'],
            ['key' => 'shop_state', 'dataIndex' => 'shop_state', 'title' => '店铺状态'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '店铺创建时间'],
            ['key' => 'shop_end_time', 'dataIndex' => 'shop_end_time', 'title' => '店铺关闭时间'],
            ['key' => 'status_text', 'dataIndex' => 'status_text', 'title' => '申请状态'],
            ['key' => 'is_own_shop_text', 'dataIndex' => 'is_own_shop_text', 'title' => '是否自营店铺'],
            ['key' => 'shop_type_text', 'dataIndex' => 'shop_type_text', 'title' => '店铺类型'],
            ['key' => 'store_code', 'dataIndex' => 'store_code', 'title' => '店铺编码'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields($is_show = '')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function listItems()
    {
        return Shop::select()->paginate(config('app.per_page'));
    }

    /**
     * 店铺信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        return Shop::find($id);
    }

    /**
     * 搜索店铺
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return mixed
     */
    public function search($request, $sign = '', $isDown = false)
    {
        $shopModel = new Shop();

        $request['per_page'] = isset($request['per_page']) && $request['per_page'] ? $request['per_page'] : config('app.per_page');

        //店铺分类
        if (isset($request['rel_cat_id'])) {
            $shopModel = $shopModel->select('shops.*')->leftJoin('shop_class_relations', 'shop_class_relations.shop_id', '=', 'shops.id')->where('shop_class_relations.class_id', $request['rel_cat_id']);
        }


        if (isset($request['sign']) && $request['sign'] == 'index-shop') {
            $orderby = 'id';
            $direction = 'desc';

            if (isset($request['orderby'])) {
                $orderby = $request['orderby'];
                unset($request['orderby']);
            }
            if (isset($request['direction']) && in_array($request['direction'], ['desc', 'asc'])) {
                $direction = $request['direction'];
                unset($request['direction']);
            }
            $shopModel = filterModel($shopModel, $this->filterables, $request);

            $lists = $shopModel->select('id', 'shop_name', 'address', 'longitude', 'latitude')->orderBy($orderby, $direction)->get();
        } else {

            $shopModel = filterModel($shopModel, $this->filterables, $request);
            if (!$isDown) {
                if ($sign) {
                    $lists = $shopModel->orderBy('id', 'desc')->paginate($request['per_page']);
                } else {
                    //            $lists = $shopModel->where('shop_type', '=', 'brand')->orderBy('id', 'desc')->paginate($request['per_page']);
                    $lists = $shopModel->where('internal', '=', '1')->orderBy('id', 'desc')->paginate($request['per_page']);
                }
            } else {

                $lists = $shopModel->orderBy('id', 'desc')->get();
            }

        }

        return $lists;
    }
}