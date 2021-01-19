<?php
/**
 * @Filename UserAccountObserver.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Observers;

use ShopEM\Models\UserAccount;
use ShopEM\Models\UserProfile;
use ShopEM\Models\UserExperience;
use ShopEM\Models\UserPoint;
use ShopEM\Models\UserGrade;


// creating, created, updating, updated, saving,
// saved,  deleting, deleted, restoring, restored

class UserAccountObserver
{

    /**
     * 监听数据创建后的事件
     *
     * @Author hfh_wind
     * @param UserAccount $user
     */
    public function created(UserAccount $user)
    {
        if ($user->id > 0) {
            $user_id=$user->id;
            $UserProfile=self::__preUser($user_id);
            //补上附表记录
            UserProfile::create($UserProfile);
            UserExperience::create(['user_id'=>$user_id]);
            UserPoint::create(['id'=>$user_id]);
        }

    }


    /**
     * 预处理注册会员基础信息
     *
     * @param int $userId
     * @param array $data
     *
     * @return array
     */
    private static  function __preUser($userId)
    {
        $user['id'] = $userId;
        $user['reg_ip'] = request()->getClientIp();
        $grade = UserGrade::where(['default_grade' => '1'])->first();
        $account['grade_id'] = $grade['grade_id'] ? $grade['grade_id'] : '0';
        return $user;
    }


}