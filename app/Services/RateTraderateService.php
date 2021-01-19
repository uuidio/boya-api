<?php
/**
 * @Filename        RateTraderateService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\RateTraderate;

class RateTraderateService
{

    /**
     * 新增一个订单的评价
     *
     * @Author djw
     * @param $data 评价的内容
     * @param bool $isShopDsr
     * @return bool
     */
    public static function createRate($data, $isShopDsr=true)
    {
        $tradeData = Trade::where('tid', $data['tid'])->first();

        //验证订单是否可以评价
        self::__checkTradeRate($tradeData,$data);

        $isBuyerRateCount = 0;//需要评价的子订单数量
        $tradeOrderData = [];
        foreach( $tradeData['trade_order'] as $key => $value )
        {
            //没申请售后并且订单完成，没有评价的子订单可以进行评价
            if( $value['buyer_rate'] == '0' && $value['status'] == 'TRADE_FINISHED' )
            {
                $oid = $value['oid'];
                $tradeOrderData[$oid] = $value;
                $isBuyerRateCount++;//需要评价的子订单数量
            }
            elseif( $value['buyer_rate'] == '1')
            {
                //有子订单进行过评价，那么则不需要进行店铺动态评分
                $isShopDsr = false;
            }
        }

        //没有需要评价的子订单
        if( !$isBuyerRateCount )
        {
            throw new \LogicException('不需要评价');
        }

        DB::beginTransaction();
        try{
            if( $isShopDsr )//需要进行店铺动态评分
            {
                //添加店铺动态评分
                if( !RateScoreService::addScore($tradeData['tid'], $tradeData['shop_id'],$tradeData['user_id'], $data) )
                {
                    throw new \LogicException('店铺动态评分失败');
                }
            }

            $successRateCount = 0;//此次评价成功的数量
            $successRateOid = array();//此次成功评价的子订单号
            foreach( $data['rate_data'] as $rateData )
            {
                if( $tradeOrderData[$rateData['oid']] )
                {
                    $rateId = self::__createItemRate($tradeData['tid'], $rateData, $tradeOrderData[$rateData['oid']]);
                    $successRateOid[] = $rateData['oid'];
                    $successRateCount++;
                }
                else
                {
                    throw new \LogicException('参数错误');
                }
            }

            if( $isBuyerRateCount && $isBuyerRateCount == $successRateCount )
            {
                $tradeBuyerRate = 1;
                //2 将子订单表中的是否评价字段修改为已评价 tradeApi改造点
                if( !Trade::where('tid',$tradeData['tid'])->update(['buyer_rate'=>'1']))
                {
                    throw new \LogicException('更新子订单评价状态失败');
                }
            }

            if( $successRateOid )
            {
                if( !TradeOrder::where('oid',$successRateOid)->update(['buyer_rate'=>'1']) )
                {
                    throw new \LogicException('更新订单评价状态失败');
                }
            }

            DB::commit();
            return true;
        }
        catch(\LogicException $e)
        {
            DB::rollBack();
            throw new \LogicException($e->getMessage());
        }
    }

    /**
     * 用户进行评价的时候调用此方法验证，验证订单中的子订单是否可以评价
     *
     * @Author djw
     * @param $tradeData
     * @param $data
     * @return bool
     */
    private static function __checkTradeRate($tradeData,$data)
    {
        if( empty($tradeData) )
        {
            throw new \LogicException('评价的订单不存在');
        }

        if( $tradeData['status'] != 'TRADE_FINISHED' )
        {
            throw new \LogicException('评价的订单未完成');
        }

        if( !$data['user_id'] || $tradeData['user_id'] != $data['user_id'] )
        {
            throw new \LogicException('无操作权限,可能已退出登录，请重新登录');
        }

        return true;
    }

    /**
     * 新增单个子订单的评价
     *
     * @Author djw
     * @param $tid
     * @param $data
     * @param $tradeOrderData
     * @param bool $test
     * @return mixed
     */
    private static function __createItemRate($tid, $data, $tradeOrderData, $test=false)
    {
        if( !$test )//测试模式不需要进行验证判断,主要用于测试用例和评价自动完成
        {
            //检查评价提交的数据是否合法
            self::__checkRateData($data);
        }

        //评论参数
        $traderateInsert['tid'] = $tid;
        $traderateInsert['oid'] = $data['oid'];
//        $traderateInsert['trade_end_time'] = $tradeOrderData['end_time'];
        $traderateInsert['user_id'] = $tradeOrderData['user_id'];
        $traderateInsert['shop_id'] = $tradeOrderData['shop_id'];
        $traderateInsert['goods_id'] = $tradeOrderData['goods_id'];
        $traderateInsert['goods_name'] = $tradeOrderData['goods_name'];//冗余商品名称，用于查询
        $traderateInsert['spec_nature_info'] = $tradeOrderData['sku_info'];//冗余货品描述
        $traderateInsert['goods_image'] = $tradeOrderData['goods_image'];//冗余图片
        $traderateInsert['goods_price'] = $tradeOrderData['goods_price'];//冗余单价
        $traderateInsert['content'] = trim($data['content']);//todo 需要做防xss处理
        $traderateInsert['rate_pic'] = $data['rate_pic'];
        $traderateInsert['result'] = $data['result'];
        $traderateInsert['is_appeal'] = ($data['result'] == 'good') ? 0 : 1;//如果为好评则不需要申诉
        $traderateInsert['anony'] = $data['anony'];

        $rateId = RateTraderate::create($traderateInsert);
        if(!$rateId)
        {
            throw new \LogicException('评价提交失败');
        }

        $filter = [
            'rate_good_count' => 0,
            'rate_bad_count' => 0,
            'rate_neutral_count' => 0,
        ];

        $filter['goods_id'] = $tradeOrderData['goods_id'];
        if( $data['result'] == 'good' )
        {
            $filter['rate_good_count'] = 1;
        }
        elseif( $data['result'] == 'bad' )
        {
            $filter['rate_bad_count'] = 1;
        }
        else
        {
            $filter['rate_neutral_count'] = 1;
        }

        $updateResult = GoodsCountService::updateRateQuantity($filter);
        if( !$updateResult )
        {
            throw new \LogicException('更新评价数量失败');
        }

        return $rateId;
    }

    /**
     * 用户在开启修改评论权限的情况下
     */
    /**
     * @Author djw
     * @param $rateId
     * @param $data
     * @return mixed
     */
    public static function updateRate($rateId, $data)
    {
        self::__checkUpdateData($rateId, $data);
        $data['is_lock'] = 1;

        $rowData = RateTraderate::select('result','goods_id')->find($rateId);
        if( empty($rowData) )
        {
            throw new \LogicException('修改的评价不存在');
        }

        $filter = [
            'rate_good_count' => 0,
            'rate_bad_count' => 0,
            'rate_neutral_count' => 0,
        ];
        //更新商品评价数量统计
        $filter['goods_id'] = $rowData['goods_id'];
        if( $rowData['result'] == 'bad' )
        {
            $filter['rate_bad_count'] = -1;
        }
        else
        {
            $filter['rate_neutral_count'] = -1;
        }

        if( $data['result'] == 'good' )
        {
            $filter['rate_good_count'] += 1;
        }
        elseif( $data['result'] == 'bad' )
        {
            $filter['rate_bad_count'] += 1;
        }
        else
        {
            $filter['rate_neutral_count'] += 1;
        }

        $updateResult = GoodsCountService::updateRateQuantity($filter);
        if( !$updateResult )
        {
            throw new \LogicException('更新评价数量失败');
        }

        return RateTraderate::where('id',$rateId)->update($data);
    }

    private static function __checkUpdateData($rateId, $data)
    {
        $rateData = RateTraderate::select('id','user_id','is_lock')->find($rateId);
        if( empty($rateData) )
        {
            throw new \LogicException('还未评价');
        }

        if( !$data['user_id'] || $rateData['user_id'] != $data['user_id'] )
        {
            throw new \LogicException('无操作权限,可能已退出登录，请重新登录');
        }

        if( $rateData['is_lock'] )
        {
            throw new \LogicException('无修改评价权限');
        }

        $ratePic = explode(',',$data['rate_pic']);
        if( count($ratePic) > 5 )
        {
            throw new \LogicException('晒单最多上传5张图片');
        }

        if( !in_array($data['result'],['good','neutral','bad']) )
        {
            throw new \LogicException('请检查商品评分参数是否正确');
        }

        return true;
    }

    /**
     * 检查评价提交的数据是否合法
     */
    private static function __checkRateData($data)
    {
        $rateData = RateTraderate::select('id','user_id','is_lock')->where('oid', $data['oid'])->first();
        if( !empty($rateData) )
        {
            throw new \LogicException('该订单已评价');
        }

        if( $data['content'] && mb_strlen(trim($data['content']),'utf8') > 300 )
        {
            throw new \LogicException('评价内容不能超过300个字');
        }

        $ratePic = explode(',',$data['rate_pic']);
        if( count($ratePic) > 5 )
        {
            throw new \LogicException('晒单最多上传5张图片');
        }

        if( !in_array($data['result'],['good','neutral','bad']) )
        {
            throw new \LogicException('请检查评价结果参数是否正确');
        }

        if( $data['result'] == 'bad' && empty($data['content']) )
        {
            throw new \LogicException('请填写差评理由');
        }

        return true;
    }

    /**
     * 商家解释，回复评论
     *
     * @param int $rateId 评论ID
     * @param string $content 回复内容
     *
     */
    public static function reply($rateId, $content, $shopId)
    {
        if( empty($content) || mb_strlen($content,'utf8') > 300 || mb_strlen($content,'utf8') < 5 )
        {
            throw new \LogicException('请填写5-300个字的回复内容');
        }

        $rateData = RateTraderate::select('id','shop_id','is_reply')->find($rateId);
        if( $rateData['shop_id'] != $shopId )
        {
            throw new \LogicException('无操作权限,可能已退出登录，请重新登录');
        }
        if( $rateData['is_reply'] )
        {
            throw new \LogicException('该评论已回复');
        }

        $updateData['reply_content'] = $content;
        $updateData['is_reply'] = 1;
        $updateData['reply_time'] = time();

        return RateTraderate::where('id',$rateId)->update($updateData);
    }

    /**
     * 设置评价为匿名
     *
     * @param int $rateId 评价ID 1 匿名 0 实名
     */
    public static function setAnony($rateId, $userId)
    {
        if( empty($rateId) ) return false;

        $rateData = RateTraderate::select('id','user_id','anony','oid')->find($rateId);
        if( empty($rateData) ) return true;
        if( !$verify )
        {
            self::__verifySetAnony($rateData, $userId);
        }

        return RateTraderate::where('id',$rateId)->update(['anony'=>'1']);
    }

    //检查是否可以设置匿名
    private static function __verifySetAnony($rateData, $userId)
    {
        foreach( (array)$rateData as $row )
        {
            if( $row['user_id'] != $userId )
            {
                throw new \LogicException('无操作权限,可能已退出登录，请重新登录');
            }

            if( $rateData['anony'] == '1' )
            {
                throw new \LogicException('已是匿名不需要设置');
            }
        }

        return true;
    }
}