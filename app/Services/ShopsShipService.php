<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-07-11 15:49:51
 * @version 	V1.0
 */

namespace ShopEM\Services;


use ShopEM\Models\ShopShip;

class ShopsShipService
{
	/**
	 * [checkGoods 检查商品是否已绑定]
	 * @Author mssjxzw
	 * @param  [type]  $goods_id [description]
	 * @param  [type]  $shop_id  [description]
	 * @return [type]            [description]
	 */
	public function checkGoods($goods_id,$shop_id)
	{
        $post = ShopShip::where('shop_id',$shop_id)->get();
        if (count($post) > 0) {
            foreach ($post as $key => $value) {
            	if (isset($value->goods_ids[$goods_id])) {
            		return ['code'=>1,'msg'=>'已绑定'];
            	}
            }
        }
        return ['code'=>0,'msg'=>''];
	}
}