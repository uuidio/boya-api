<?php
/**
 * @Filename        RateAppealService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\RateTraderate;
use ShopEM\Models\RateAppeal;

class RateAppealService
{

    /**
     * 新增一个订单申诉
     *
     * @Author djw
     * @param $data 申诉的内容
     * @param bool $isShopDsr
     * @return bool
     */
    public static function createAppeal($data, $isShopDsr=true)
    {
        self::__checkAppealData($data,$data['shop_id']);

        if( $data['is_again'] )
        {
            $updateRateData['appeal_again'] = 1;
            $flag = self::__againAppeal($data);
        }
        else
        {
            $appealData = RateAppeal::where('rate_id', $data['rate_id'])->select('rate_id')->first();
            if( $appealData )
            {
                throw new \LogicException('评价已经申诉');
            }
            $insertData['rate_id'] = $data['rate_id'];
            $insertData['content'] = trim($data['content']);//todo 需要做防xss处理
            $insertData['evidence_pic'] = $data['evidence_pic'];
            $insertData['appeal_type'] = $data['appeal_type'] == 'APPLY_UPDATE' ? 'APPLY_UPDATE' : 'APPLY_DELETE';

            $gm_id = \ShopEM\Models\Shop::where('id',$data['shop_id'])->value('gm_id');
            $insertData['gm_id'] = $gm_id;
            $flag = RateAppeal::create($insertData);
        }

        if( !$flag )  return false;

        //更新评论表是否需要申诉的判断为不需求申诉
        $updateRateData['is_appeal'] = 0;//1 为可以申诉，0为不可以申诉
        $updateRateData['appeal_status'] = 'WAIT';//评价表中存储申诉状态用户筛选
        $updateRateData['appeal_time'] = time();//评价表中存储申诉时间用户筛选
        $result = RateTraderate::where('id', $data['rate_id'])->update($updateRateData);

        return $result ? true : false;
    }

    /**
     * 对首次驳回的申诉进行再次申诉
     */
    private static function __againAppeal($data)
    {
        $appealData = RateAppeal::where('rate_id', $data['rate_id'])->select('id','rate_id','content','evidence_pic','created_at','reject_reason')->first();
        if( empty($appealData) )
        {
            throw new \LogicException('不可以再次申诉，请先进行第一次申诉');
        }

        if( empty($data['evidence_pic']) )
        {
            throw new \LogicException('申诉图片凭证必填');
        }

        $updateData['content'] = trim($data['content']);//todo 需要做防xss处理
        $updateData['evidence_pic'] = $data['evidence_pic'];
        $updateData['status'] = 'WAIT';
        $updateData['reject_reason'] = '';
        $updateData['appeal_again'] = 1;
        $updateData['appeal_type'] = $data['appeal_type'];
        $updateData['appeal_log'] = ['content'=>$appealData['content'],'evidence_pic'=>$appealData['evidence_pic'],'appeal_time'=>$appealData['appeal_time'],'reject_reason'=>$appealData['reject_reason'] ];

        $flag = RateAppeal::where('id', $appealData['id'])->update($updateData);
        if( !$flag )  return false;

        return true;
    }

    /**
     * 平台审核商家申诉
     *
     * @param int $appeal 申诉ID
     * @param array $data
     */
    public static function check($appealId, $data)
    {
        $appealData = RateAppeal::where('id', $appealId)->select('id','rate_id','status','appeal_again','appeal_type')->first();
        if( empty($appealData) )
        {
            throw new \LogicException('审核的申诉不存在');
        }

        if( $appealData['status'] != 'WAIT' )
        {
            throw new \LogicException('申诉已审核');
        }

        if( $data['result'] == 'true' )//申诉通过
        {
            $flag = self::__AppealSuccessAfter($appealData['rate_id'], $appealId, $appealData['appeal_type']);
        }
        else
        {
            $flag = self::__AppealRejectAfter($appealData['rate_id'], $appealId, $data['reject_reason'], $appealData['appeal_again']);
        }

        return $flag;
    }

    /**
     * 申诉成功的后续处理，如果申诉为修改则评价锁定打开，申诉为删除的则删除评论
     *
     * @param int    $rateId     评论ID
     * @param int    $appealId   申诉ID
     * @param string $appealType 申诉类型
     */
    private static function __AppealSuccessAfter($rateId, $appealId, $appealType)
    {
        $updateData['status'] = 'SUCCESS';

        DB::beginTransaction();

        $flag = RateAppeal::where('id', $appealId)->update($updateData);
        if( !$flag )
        {
            DB::rollBack();
            return false;
        }

        if( $appealType == 'APPLY_DELETE' )
        {
            $rateData = RateTraderate::where('id', $rateId)->select('id','result','goods_id')->first();
            if( !$rateData )
            {
                DB::rollBack();
                return false;
            }

            $filter = [
                'rate_good_count' => 0,
                'rate_bad_count' => 0,
                'rate_neutral_count' => 0,
            ];

            if( $rateData['result'] == 'good' )
            {
                $filter['rate_good_count'] = -1;
            }
            elseif( $rateData['result'] == 'bad' )
            {
                $filter['rate_bad_count'] = -1;
            }
            else
            {
                $filter['rate_neutral_count'] = -1;
            }
            $filter['goods_id'] = $rateData['goods_id'];
            if( !GoodsCountService::updateRateQuantity($filter) )
            {
                DB::rollBack();
            }
//            $updateRateData['disabled'] = 1;
            $result = RateTraderate::where('id', $rateId)->delete();
        }
        else
        {
            $updateRateData['is_lock'] = 0;//打开锁定
            $updateRateData['is_appeal'] = 0;//不可以申诉
            $updateRateData['appeal_status'] = 'SUCCESS';
            $result = RateTraderate::where('id', $rateId)->update($updateRateData);
        }
        if( !$result )
        {
            DB::rollBack();
            return false;
        }

        DB::commit();
        return true;
    }

    /**
     * 审核申诉拒绝处理
     *
     * @param int    $rateId     评论ID
     * @param int    $appealId   申诉ID
     * @param string $reason     申诉理由
     * @param bool   $isAgain    是否可以再次申诉
     */
    private static function __AppealRejectAfter($rateId, $appealId, $reason, $isAgain)
    {
        if( !$isAgain )//不是再次(第二轮)申诉，首次申诉
        {
            $updateData['status'] = 'REJECT';

            $updateRateData['is_appeal'] = 1;//1 为可以申诉，0为不可以申诉
            $updateRateData['appeal_status'] = 'REJECT';
        }
        else//再次申诉驳回
        {
            $updateData['status'] = 'CLOSE';

            $updateRateData['is_appeal'] = 0;
            $updateRateData['appeal_status'] = 'CLOSE';
        }

        $updateData['reject_reason'] = $reason;
        $result = RateTraderate::where('id', $rateId)->update($updateRateData);
        if( !$result )  return false;

        $flag = RateAppeal::where('id', $appealId)->update($updateData);
        return $flag ? true : false;
    }

    /**
     * 检查申诉传入的数据是否合法
     */
    private static function __checkAppealData($data,$shopId)
    {
        $rateData = RateTraderate::select('id','is_appeal','shop_id')->find($data['rate_id']);
        if( empty($rateData) )
        {
            throw new \LogicException('要申诉的评论不存在');
        }

        if( $rateData['shop_id'] != $shopId)
        {
            throw new \LogicException('无操作权限,可能已退出登录，请重新登录');
        }

        if( $rateData['is_appeal'] == 0 )
        {
            throw new \LogicException('该评论不能申诉');
        }

        if( empty($data) || mb_strlen(trim($data['content']),'utf8') > 300 || mb_strlen(trim($data['content']),'utf8') < 5 )
        {
            throw new \LogicException('请填写5-300个字的内容');
        }

        $evidencePic = explode(',',$data['evidence_pic']);
        if( $evidencePic && count($evidencePic) > 5 )
        {
            throw new \LogicException('申诉最多上传5张图片');
        }

        return true;
    }
}