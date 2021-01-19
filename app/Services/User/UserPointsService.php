<?php
/**
 * @Filename        UserPointsService.php
 *
 */
namespace ShopEM\Services\User;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\UserPoint;
use ShopEM\Models\UserPointLog;

class UserPointsService
{

    /**
     * 处理会员过期积分
     *
     * @param int $userId
     * @return bool
     */
    public static function pointExpiredCount($userId=null)
    {
//        $expiredMonth = app::get('sysconf')->getConf('point.expired.month');
        $expiredMonth = 12;//todo 暂无会员积分过期月份设置
        $expiredMonth = $expiredMonth ? $expiredMonth : 12;
        $expiredTime = strtotime(date('Y-'.$expiredMonth.'-01 23:59:59')."-1 year +1 month -1 day");

        //error_log(date('Y-m-d H:i:s',$expiredTime)."------\n",3,DATA_DIR."/bbb.log");

        if(time() >= $expiredTime)
        {
            $userPoints = UserPoint::select('point_count','expired_point','expired_time')->find($userId);
            //error_log(date('Y-m-d H:i:s',$userPoints['expired_time'])."------\n",3,DATA_DIR."/bbb.log");

            if(isset($userPoints['expired_time']) && $userPoints['expired_time'] && $userPoints['expired_time'] != $expiredTime)
            {
                return true;
            }

            if($userPoints['expired_point'] <= 0)
            {
                return true;
            }

            $newExpiredTime = strtotime(date('Y-'.$expiredMonth.'-01 23:59:59')." +1 month -1 day");
            $userPoints['expired_point'] = $userPoints['point_count'] = $userPoints['point_count']-$userPoints['expired_point'];
            $userPoints['expired_time'] = $newExpiredTime;
            DB::beginTransaction();
            try
            {
                $result = UserPoint::where('id', $userId)->update($userPoints);
                $result = UserPointLog::where('user_id',$userId)->where('modified_time', '>', $expiredTime)->delete();
            }
            catch(\LogicException $e)
            {
                DB::rollback();
                $msg = $e->getMessage();
//                logger::info('point_expired:'.$msg);
                return false;
            }
            DB::commit();
            return true;
        }
        return true;
    }

    /**
     * @brief 积分改变
     *
     * @param $params
     *
     * @return
     */
    public static function changePoint($params)
    {
        if(!$params['user_id'])
        {
            throw new \Exception('会员参数错误');
        }
        if(!$params['modify_point'])
        {
            throw new \Exception('会员积分参数错误');
        }

        DB::beginTransaction();
        try{
            $data['user_id'] = $params['user_id'];
            $data['remark'] = isset($params['modify_remark']) && $params['modify_remark'] ? $params['modify_remark'] : "平台修改";
            $data['point'] = abs($params['modify_point']);
            if($params['modify_point'] >= 0)
            {
                $data['behavior_type'] = "obtain";
                $data['behavior'] = isset($params['behavior']) && $params['behavior'] ? $params['behavior'] : "平台手动增加积分";
                $result = self::add($data['user_id'],$data['point']);
            }
            elseif($params['modify_point'] < 0)
            {
                $data['behavior_type'] = "consume";
                $data['behavior'] = isset($params['behavior']) && $params['behavior'] ? $params['behavior'] : "平台手动扣减积分";
                $result = self::deduct($data['user_id'],$data['point']);
            }
            if(!$result)
            {
                throw new \Exception('会员积分值更改失败');
            }
            $result = UserPointLog::create($data);
            if(!$result)
            {
                throw new \Exception('会员积分值明细记录失败');
            }
            DB::commit();
            return true;
        }catch(\LogicException $e){
            DB::rollback();
            throw new \Exception($e->getMessage());
            return false;
        }
    }

    /**
     * 积分增加
     *
     * @Author djw
     * @param $userId
     * @param $point
     * @return bool
     */
    public static function add($userId,$point)
    {
        $pointInfo = UserPoint::select('id','point_count')->find($userId);
        if($pointInfo)
        {
            $point += $pointInfo['point_count'];
            $result = UserPoint::where('id', $userId)->update(['point_count' => $point]);
        }
        else
        {
            $result = UserPoint::create(['id' => $userId, 'point_count' => $point]);
        }
        if(!$result)
        {
            return false;
        }
        return true;
    }

    /**
     * 积分消耗
     *
     * @Author djw
     * @param $userId
     * @param $point
     * @return bool
     * @throws \Exception
     */
    public static function deduct($userId,$point)
    {
        $pointInfo = UserPoint::select('id','expired_point','point_count')->find($userId);
        if($pointInfo)
        {
            if($pointInfo['expired_point'] > 0)
            {
                $expired = ($pointInfo['expired_point'] < $point) ? $pointInfo['expired_point'] : $point;
                $expiredPoint = $pointInfo['expired_point'] - $expired;
                $point = $pointInfo['point_count'] - $point;
                $result = UserPoint::where('expired_point - '.$expired, '>=', 0)->where('id', $userId)->update(['expired_point' => $expiredPoint, 'point_count' => $point]);
            }
            else
            {
                $point = $pointInfo['point_count'] - $point;
                $result = UserPoint::where('id', $userId)->update([ 'point_count' => $point]);
            }

            if(!$result)
            {
                return false;
            }
            return true;
        }
        else
        {
            throw new \Exception('该用户没有积分，不能减积分');
        }
    }



}