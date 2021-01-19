<?php
/**
 * @Filename        RateAppendService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\RateTraderate;
use ShopEM\Models\RateAppend;
use ShopEM\Models\Trade;

class RateAppendService
{

    /**
     * 新增一个订单追评
     *
     * @Author djw
     * @param $data 追评的内容
     * @param bool $isShopDsr
     * @return bool
     */
    public static function createAppend($params)
    {
        $data = RateTraderate::select('id','user_id','anony','oid','created_at','shop_id')->where('user_id', $params['user_id'])->where('id', $params['rate_id'])->where('is_append', '0')->first();
        if( empty($data) )
        {
            throw new \LogicException('追评的评价不存在或已追评');
        }

//        $day = (int)app::get('sysconf')->getConf('rate.append.time') ?: 30; //todo 需要在后台可配置 追评时间限制
        $day = 30;
        if( round((time() - strtotime($data['created_at']))/86400) >= $day )
        {
            throw new \LogicException('超出追评时间限制，不可以在追评');
        }

        //新增追评功能，以前的评价没有存储订单结束时间，这里兼容
        $tradeData['end_time'] = '';
        if( !$data['trade_end_time'] )
        {
            $params['tid'] = $data['tid'];
            $params['fields'] = 'tid,end_time';
            $tradeData = Trade::select('tid','end_time')->where('tid', $data['tid'])->first();
        }

        $ratePic = explode(',',$params['pic']);
        if( count($ratePic) > 5 )
        {
            throw new \LogicException('晒单最多上传5张图片');
        }

        $insertData['rate_id'] = $params['rate_id'];
        $insertData['append_content'] = $params['content'];
        $insertData['append_rate_pic'] = $params['pic'];
        $insertData['shop_id'] = $data['shop_id'];
        $insertData['trade_end_time'] = $data['trade_end_time'] ?: $tradeData['end_time'] ;

        DB::beginTransaction();
        try
        {
            $appendRateId = RateAppend::create($insertData);
            $update = RateTraderate::where('id',$params['rate_id'])->update(['is_append'=>'1']);
            if(!$appendRateId || !$update )
            {
                throw new \LogicException('保存失败');
            }
            DB::commit();
        }
        catch(\LogicException $e)
        {
            DB::rollBack();
            throw new \LogicException($e->getMessage());
        }

        return ['append_rate_id'=>$appendRateId];
    }

    /**
     * 商家回复追加评价
     *
     * @Author djw
     * @param $params
     * @return mixed
     */
    public static function reply($rateId, $content, $shopId)
    {
        if( empty($content) || mb_strlen($content,'utf8') > 300 || mb_strlen($content,'utf8') < 5 )
        {
            throw new \LogicException('请填写5-300个字的回复内容');
        }

        $appendData = RateAppend::select('append_rate_id','shop_id','is_reply')->where('rate_id', $rateId)->first();
        if( $appendData['shop_id'] != $shopId )
        {
            throw new \LogicException('无操作权限,可能已退出登录，请重新登录');
        }
        if( $appendData['is_reply'] )
        {
            throw new \LogicException('该评论已回复');
        }
        $set = [
            'append_reply_content' => $content,
            'is_reply' => '1',
            'reply_time'=> time(),
        ];
        return RateAppend::where('rate_id', $rateId)->where('is_reply', 0)->where('shop_id', $shopId)->update($set);
    }


}