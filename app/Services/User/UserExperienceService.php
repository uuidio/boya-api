<?php
/**
 * @Filename        UserExperienceService.php
 *
 */
namespace ShopEM\Services\User;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserExperience;

class UserExperienceService
{

    public static function updateUserExp($params)
    {
        $userId = $params['user_id'];unset($params['user_id']);
        $row = UserAccount::select('experience')->find($userId);
        switch($params['type'])
        {
            case "obtain":
                $params['experience'] = ceil($row['experience'] + $params['num']);
                break;
            case "consume":
                $params['experience'] = ceil($row['experience'] - $params['num']);
                break;
        }

        $paramsExp['experience'] = ceil($params['num']);
        $paramsExp['behavior_type'] = $params['type'];
        $paramsExp['behavior'] = $params['behavior'];
        $paramsExp['remark'] = $params['remark'];
        $paramsExp['user_id'] = $userId;

        unset($params['type'],$params['num'],$params['behavior'],$params['remark']);

        if($params['experience'] < 0) $params['experience'] = 0;

        $params['grade_id'] = UserGradeService::upgrade($params['experience']);

        DB::beginTransaction();

        try
        {
            $result = UserAccount::where('id', $userId)->update($params);
            if(!$result)
            {
                throw new \LogicException('会员保存失败');
            }
            $result = UserExperience::create($paramsExp);
            if(!$result)
            {
                throw new \LogicException('会员经验值保存失败');
            }
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollback();
            throw $e;
        }

        return $result;
    }



}