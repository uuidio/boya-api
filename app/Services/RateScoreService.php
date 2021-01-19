<?php
/**
 * @Filename        RateScoreService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Services;

use ShopEM\Models\RateScore;

class RateScoreService
{
    /**
     * 新增一个完成订单的动态评分
     *
     * @param int $tid 动态评分的订单ID
     */
    public static function addScore($tid, $shopId, $userId, $data)
    {
        //检查店铺动态评分提交的数据是否合法
        self::__checkScoreData($tid, $data);

        $scoreInsert['cat_id'] = 0;
        $scoreInsert['tid'] = $tid;
        $scoreInsert['user_id'] = $userId;
        $scoreInsert['shop_id'] = $shopId;
        $scoreInsert['tally_score'] = $data['tally_score'];
        $scoreInsert['attitude_score'] = $data['attitude_score'];
        $scoreInsert['delivery_speed_score'] = $data['delivery_speed_score'];
        $scoreInsert['logistics_service_score'] = $data['logistics_service_score'];

        self::__setInvalidScore($scoreInsert);

        return RateScore::create($scoreInsert);
    }

    /**
     * 设置无效商家动态评分
     */
    private static function __setInvalidScore($scoreInsert)
    {
        //当前月内，同一用户对同一商家进行动态评分，有效评分为3次，覆盖方式进行计算
        $month_start = date('Y-m-01 H:i:s', time());
        $scoreData = RateScore::where('user_id', $scoreInsert['user_id'])
            ->where('shop_id', $scoreInsert['shop_id'])
            ->where('disabled', 0)
            ->whereDate('created_at', '>=', $month_start)
            ->get();
        if( count($scoreData) == 3 )
        {
            //将取出的三条的最后一条数据评价设置为无效
            RateScore::where('tid', $scoreData[2]['tid'])->update(['disabled'=>1]);
        }
        return true;
    }

    /**
     * 检查店铺动态评分数据是否合法
     */
    private static function __checkScoreData($tid, $data)
    {
        $scoreInfo = RateScore::where(['tid'=>$tid])->first();
        if( $scoreInfo )
        {
            throw new \LogicException('订单已评价');
        }

        $params['tally_score'] = $data['tally_score'];
        $params['attitude_score'] = $data['attitude_score'];
        $params['delivery_speed_score'] = $data['delivery_speed_score'];
        $params['logistics_service_score'] = $data['logistics_service_score'];
        foreach( (array)$params as $score )
        {
            if( !$score || $score < 1 || $score > 5 )
            {
                throw new \LogicException('请选择店铺动态评分');
            }
        }
        return true;
    }
}