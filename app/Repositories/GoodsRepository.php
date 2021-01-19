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
use ShopEM\Models\Goods;
use ShopEM\Models\Shop;
use ShopEM\Models\ShopAttr;

class GoodsRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id'   => ['field' => 'goods.gm_id', 'operator' => '='],
        // 'keyword' => ['field' => 'goods_name', 'operator' => 'like'],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'gc_id'   => ['field' => 'gc_id', 'operator' => '='],
        'gc_id_2'   => ['field' => 'gc_id_2', 'operator' => '='],
        'gc_id_1'   => ['field' => 'gc_id_1', 'operator' => '='],
        'orderby'   => ['field' => 'orderby', 'operator' => '='],
        'direction'   => ['field' => 'direction', 'operator' => '='],
        'brand_id'  => ['field' => 'brand_id', 'operator' => '='],
        'goods_state'  => ['field' => 'goods_state', 'operator' => '='],
        'use_state'  => ['field' => 'goods_state', 'operator' => '!='],
        'start_price'  => ['field' => 'goods_price', 'operator' => '>='],
        'stop_price'  => ['field' => 'goods_price', 'operator' => '<='],
        'is_point_activity'  => ['field' => 'is_point_activity', 'operator' => '='],
        'is_rebate'  => ['field' => 'is_rebate', 'operator' => '='],
        'shop_not_in' => ['field' => 'goods.shop_id', 'operator' => 'not_in'],
        'store_id' => ['field' => 'shop_id', 'operator' => '='],
        'good_id' => ['field' => 'id', 'operator' => '='],//商品id搜索
        'goods_shop_c_lv1' => ['field' => 'goods.goods_shop_c_lv1', 'operator' => '='],
        'goods_shop_c_lv2' => ['field' => 'goods.goods_shop_c_lv2', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields($is_show='')
    {
        //根据前端要求修改返回的数据格式
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'goods_image', 'title' => '商品主图','scopedSlots'=>['customRender'=>'goods_image']],
            ['dataIndex' => 'shop_id', 'title' => '店铺ID'],
            ['dataIndex' => 'shop_name', 'title' => '所属店铺'],
            ['dataIndex' => 'goods_price', 'title' => '商品价格'],
            ['dataIndex' => 'goods_cost', 'title' => '成本价'],
            ['dataIndex' => 'goods_marketprice', 'title' => '市场价'],
            ['dataIndex' => 'goods_stock', 'title' => '商品库存'],
            ['dataIndex' => 'class_name', 'title' => '分类'],
            ['dataIndex' => 'goods_state', 'title' => '状态'],
            ['dataIndex' => 'goods_serial', 'title' => '商品货号'],
            ['dataIndex' => 'brand_name', 'title' => '品牌'],
            ['dataIndex' => 'is_rebate_text', 'title' => '是否推广商品'],
            ['dataIndex' => 'is_show_price', 'title' => '显示市场价'],
            ['dataIndex' => 'goods_shop_class_name1', 'title' => '店铺一级分类'],
            ['dataIndex' => 'goods_shop_class_name2', 'title' => '店铺二级分类'],
            ['dataIndex' => 'created_at', 'title' => '发布时间'],
            ['key' => 'gm_name','dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
        ];
    }


    /**
     * 查询字段
     *
     * @Author Huiho
     * @return array
     */
    public function downLstFields($is_show='')
    {
        //根据前端要求修改返回的数据格式
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'goods_sku', 'title' => 'SKU信息'],
            ['dataIndex' => 'shop_id', 'title' => '店铺ID'],
            ['dataIndex' => 'shop_name', 'title' => '所属店铺'],
            ['dataIndex' => 'goods_price', 'title' => '商品价格'],
            ['dataIndex' => 'goods_cost', 'title' => '成本价'],
            ['dataIndex' => 'goods_marketprice', 'title' => '市场价'],
            ['dataIndex' => 'goods_serial', 'title' => '商品货号'],
            ['dataIndex' => 'goods_stock', 'title' => '商品库存'],
            ['dataIndex' => 'class_name', 'title' => '分类'],
            ['dataIndex' => 'brand_name', 'title' => '品牌'],
            ['dataIndex' => 'good_state_text', 'title' => '状态'],
            ['dataIndex' => 'is_show_price', 'title' => '显示市场价'],
            ['dataIndex' => 'created_at', 'title' => '发布时间'],
            ['key' => 'gm_name','dataIndex' => 'gm_name', 'title' => '所属项目'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $shop_id
     * @return mixed
     */
    public function listItems($data, $recycle = false , $downData='')
    {
        $goodsModel = new Goods();
        $goodsModel = filterModel($goodsModel, $this->filterables, $data);
        // if (isset($data['shop_id|in']) && is_array($data['shop_id|in']) && $data['shop_id|in']) {
        //     $goodsModel = $goodsModel->whereIn('shop_id',$data['shop_id|in']);
        // }
         // 关键词搜索商品名称
        if(!empty($data['keyword'])){
            $goodsModel->where('goods_name', 'like','%' . trim($data['keyword']) . '%');
        }
        $goodsModel = $this->_filter($goodsModel, $data);

        //return $goodsModel->orderBy('id', 'desc')->paginate(config('app.per_page'));

        if($downData)
        {
            //下载提供数据
            $lists = $goodsModel->orderBy('id', 'desc')->get();
        }
        else
        {
            $lists = $goodsModel->orderBy('id', 'desc')->paginate(config('app.per_page'));
        }

        return $lists;
    }

    /**
     * [_filter 过滤特殊的筛选条件]
     * @param  string $model [description]
     * @param  string $data [description]
     * @return [type]        [description]
     */
    protected function _filter($model, $data)
    {
        $shopMdl = new \ShopEM\Models\Shop;
        $brandMdl = new \ShopEM\Models\Brand;
        $gmMdl = new \ShopEM\Models\GmPlatform;
        $goodClassMdl = new \ShopEM\Models\GoodsClass;

        //是否仅显示自营商品+所属店铺 shop-start
        $_shop = false;
        if (isset($data['only_self']) && $data['only_self'] == 1)
        {
            $_shop = true;
            $shopMdl = $shopMdl->where('is_own_shop','=','1');
        }
        if ( $this->_isset($data,'shop_name') )
        {
            $_shop = true;
            $shopMdl = $shopMdl->where('shop_name','like','%' . trim($data['shop_name']) . '%');
        }
        $shop_ids = [];
        if ($_shop) {
            $shop = $shopMdl->get()->toArray();
            if ($shop) {
                $shop_ids = array_column($shop, 'id');
                $shop_ids = array_unique($shop_ids);
            }
            $model = $model->whereIn('shop_id',$shop_ids);
        }
        //***shop-end

        // 品牌 brand-start
        if ( $this->_isset($data,'brand_name') )
        {
            $brand_id = -1;
            $brand = $brandMdl::where('brand_name',trim($data['brand_name']))->first();
            if($brand) $brand_id = $brand->id;
            $model = $model->where('brand_id','=',$brand_id);
        }
        // ***brand-start

        // 查询项目 swl
        $_gm = false;
        if ( $this->_isset($data,'gm_name') )
        {
            $_gm = true;
            $gmMdl = $gmMdl->where('platform_name','like','%' . trim($data['gm_name']) . '%');
        }
        $gm_ids = [];
        if ($_gm) {
            $gm = $gmMdl->get()->toArray();
            if ($gm) {
                $gm_ids = array_column($gm, 'gm_id');
                $gm_ids = array_unique($gm_ids);
            }
            // dd($gm_ids);
            $model = $model->whereIn('gm_id',$gm_ids);
        }
        // gm_name-end

        // 查询分类(匹配三级分类名称) swl
         $_class = false;
        if ( $this->_isset($data,'class_name') )
        {
            $_class = true;
            $goodClassMdl = $goodClassMdl->where('gc_name','like','%' . trim($data['class_name']) . '%');
        }
        $class_ids = [];
        if ($_class) {
            $class = $goodClassMdl->get()->toArray();
            if ($class) {
                $class_ids = array_column($class, 'id');
                $class_ids = array_unique($class_ids);
            }
            // dd($class_ids);
            $model = $model->whereIn('gc_id_3',$class_ids);
        }
        // class_name-end

        return $model;

    }

    /**
     * [_isset 存在且不为空]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    protected function _isset($data,$key)
    {
        if (isset($data[$key]) && !empty(trim($data[$key]))) {
            return true;
        }
        return false;
    }



    /**
     * 商品详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        return Goods::find($id);
    }

    /**
     * 搜索商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param int $page_count
     * @return mixed
     */
    public function search($request, $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $goodsModel = new Goods();
        $gmMdl = new \ShopEM\Models\GmPlatform;
        $gm_ids = $gmMdl->where('status',1)->pluck('gm_id');
        $selectFields[] = 'goods.*';
        //排序
        $orderbyArray = [
            'price',
            'modified_time',
            'sold_quantity',
            'on_sale_time'
        ];
        $orderby = 'goods.id';
        $direction = 'desc';
        if (isset($request['orderby']) && in_array($request['orderby'], $orderbyArray)) {
            $orderby = $request['orderby'];
            unset($request['orderby']);
        }
        if (isset($request['direction']) && in_array($request['direction'], ['desc', 'asc'])) {
            $direction = $request['direction'];
            unset($request['direction']);
        }

        if (isset($request['shop_not_in']) && $request['shop_not_in'] == 1) {
            $shop_ids = ShopAttr::where('promo_person', 0)->get()->toArray();
            $shop_ids = array_column($shop_ids, 'shop_id');
//            $goodsModel->whereNotIn('goods.shop_id',$shop_ids);
            $request['shop_not_in'] = $shop_ids;
        }

        $goodsModel = filterModel($goodsModel, $this->filterables, $request);
        $goodsModel = $goodsModel->where('goods_state', 1)->whereIn('goods.gm_id',$gm_ids);

         // 关键词搜索商品名称或商家名称
        if(!empty($request['keyword'])){
            $selectFields[] = 'shops.shop_name';
             $goodsModel->leftJoin('shops','goods.shop_id','shops.id');
             $goodsModel = $goodsModel->where(function ($query) use ($request) {
                $query->where('goods_name', 'like','%' . trim($request['keyword']) . '%')
                    ->orWhere('shop_name', 'like','%' . trim($request['keyword']) . '%');
            });
        }
        //销量排序需要连表
        if ($orderby == 'sold_quantity') {
            $selectFields[] = 'goods_count.sold_quantity';
            $goodsModel->leftJoin('goods_count','goods.id','goods_count.goods_id');
        } elseif ($orderby == 'modified_time') {
            $orderby = 'created_at';
        } elseif ($orderby == 'price') {
            $orderby = 'goods_price';
        }

        $lists = $goodsModel->select($selectFields)->orderBy($orderby, $direction)->orderBy('updated_at', 'desc')->paginate($page_count);

         // 加上商品活动和促销信息
        $lists = $lists->toArray();
        foreach ($lists['data'] as $key => &$value) {
            $value['good_sign'] = $this->goodConnectInfo($value['id']);
            // $value['good_sign'] = [];
        }
        return $lists;
    }


     // 返回商品关联的活动或促销信息
    public function goodConnectInfo($good_id)
    {
        $act_service = new \ShopEM\Services\Marketing\Activity();
        $coupon_service = new \ShopEM\Services\Marketing\Coupon();
        $activity = $act_service->getGoodInfo($good_id);

        $coupon = $coupon_service->checkGoods($good_id);
        if($coupon['code']){
            $activity['promotion'][] = '优惠券';
        }
        // dd($activity);
        return $activity;
    }


}
