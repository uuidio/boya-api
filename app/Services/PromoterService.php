<?php
/**
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-27
 * @Time: 14:16
 */
namespace ShopEM\Services;

use ShopEM\Models\ApplyPromoter;
use ShopEM\Models\UserAccount;

class PromoterService
{
    //处理数据
    public static function reprocessData($param)
    {
        $result['user_id'] = $param['user_id'];
        $result['real_name'] = $param['real_name'];
        $result['job_number'] = $param['job_number'];
        $result['mobile'] = $param['mobile'];
        $result['id_number'] = $param['id_number'] ?? '';
        $result['department'] = $param['department'] ?? '';

        $photo =
            [
                'p' => $param['id_positive'] ??'',
                'o' => $param['id_other_side'] ?? '',
            ];
        $result['id_photo'] =json_encode($photo);
        
        $info = ApplyPromoter::where('user_id',$param['user_id'])->orderBy('updated_at', 'desc')->first();
        if($info['apply_status']=='apply')
        {
            throw new \LogicException('该账号资格正在审核中,请勿重复提交!');
        }

        if(UserAccount::where('id',$param['user_id'])->where('is_promoter',1)->exists())
        {
            throw new \LogicException('该账号已经是会员!');
        }

        if(!UserAccount::where('id',$param['user_id'])->exists())
        {
            throw new \LogicException('查无此会员!');
        }
        return  $result;
    }

}