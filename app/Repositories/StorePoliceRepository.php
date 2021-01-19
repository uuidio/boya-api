<?php
/**
 * @Filename        StorePoliceRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\StorePolices;



class StorePoliceRepository
{
    /**
     * 查询数据库字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['key' => 'id', 'dataIndex' => 'id','title' => '报警值id'],
            ['key' => 'policevalue', 'dataIndex' => 'policevalue','title' => '报警值'],
            ['key' => 'created_at', 'dataIndex' => 'created_at','title' => '创建时间'],
            ['key' => 'updated_at', 'dataIndex' => 'updated_at','title' => '更新时间'],
        ];
    }



    /**
     * 店铺分类显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $shop_id
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
     * @return mixed
     */
    public function listItems($shopId)
    {
            return StorePolices::select(listFieldToSelect($this->listShowFields()))->where('shop_id',$shopId)->get();

    }




}