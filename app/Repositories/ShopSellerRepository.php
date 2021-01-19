<?php
/**
 * @Filename ShopSellerRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\SellerAccount;

class ShopSellerRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'keyword' => ['field' => 'username', 'operator' => 'like'],
        'phone' => ['field' => 'phone', 'operator' => '='],
        'seller_type' => ['field' => 'seller_type', 'operator' => '='],
        'gm_id' => ['field' => 'seller_accounts.gm_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author hfh
     * @return array
     */
    public function listFields()
    {
        return [
            ['key'=> 'id','dataIndex' => 'id', 'title' => 'ID'],
            ['key'=> 'username','dataIndex' => 'username', 'title' => '商家账号'],
            ['key'=> 'email','dataIndex' => 'email', 'title' => '商家邮箱'],
            ['key'=> 'phone','dataIndex' => 'phone', 'title' => '商家手机号码'],
            ['key'=> 'status_text','dataIndex' => 'status_text', 'title' => '是否启用'],
            ['key'=> 'seller_type_text','dataIndex' => 'seller_type_text', 'title' => '商家账号类型'],
            ['key'=> 'created_at','dataIndex' => 'created_at', 'title' => '创建时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表数据
     *
     * @Author hfh
     * @return mixed
     */
    public function listItems()
    {
        return Shop::select()->paginate(config('app.per_page'));
    }

    /**
     * 店铺信息
     *
     * @Author hfh
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
     * @Author hfh
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $SellerAccountModel = new SellerAccount();
        $SellerAccountModel = filterModel($SellerAccountModel, $this->filterables, $request);

        if (isset($request['shop_id'])) {
            $SellerAccountModel->select('seller_accounts.*')->LeftJoin('shop_rel_sellers','shop_rel_sellers.seller_id','=','seller_accounts.id')->where('shop_rel_sellers.shop_id', $request['shop_id']);
        }
        $lists = $SellerAccountModel->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}