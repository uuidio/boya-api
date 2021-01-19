<?php
/**
 * @Filename        GoodsCountService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Services;

use ShopEM\Models\GoodsCount;

class GoodsCountService
{
    /**
     * 更新商品的评论统计
     *
     * @Author djw
     * @param $params
     * @return bool
     */
    public static function updateRateQuantity($params){
        $goodsCount = GoodsCount::where('goods_id', $params['goods_id'])->first();

        if( !$params['rate_good_count'] && !$params['rate_neutral_count'] && !$params['rate_bad_count'] )
        {
            return false;
        }
        $data = [];
        $data['rate_count'] = $goodsCount['rate_count'];
        if( $params['rate_good_count'] )
        {
            $data['rate_count'] += $params['rate_good_count'];
            $data['rate_good_count'] = $params['rate_good_count'] + $goodsCount['rate_good_count'];
        }

        if( $params['rate_neutral_count'] )
        {
            $data['rate_count'] += $params['rate_neutral_count'];
            $data['rate_neutral_count'] = $params['rate_neutral_count'] + $goodsCount['rate_neutral_count'];
        }

        if( $params['rate_bad_count'] )
        {
            $data['rate_count'] += $params['rate_bad_count'];
            $data['rate_bad_count'] = $params['rate_bad_count'] + $goodsCount['rate_bad_count'];
        }

        $stmt = GoodsCount::where('goods_id',$params['goods_id'])->update($data);

        return $stmt>0?$stmt:true;
    }

    /**
     * 更新销量
     *
     * @Author ${USER}
     * @param $params
     */
    public static function updateSoldQuantity($params){
        return GoodsCount::where('goods_id', $params['goods_id'])->increment('sold_quantity', $params['quantity']);
    }

}