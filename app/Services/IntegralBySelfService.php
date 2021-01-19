<?php
/**
 * @Filename IntegralBySelfService
 * @Author Huiho
 * @date    	2020-05-9 14:38:01
 * @version 	V1.0
 */
namespace ShopEM\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\IntegralBySelf;
use ShopEM\Jobs\UpdateCrmUserInfo;

class IntegralBySelfService {


    public function _checkData($input_data)
    {
        $result = $input_data;
        $check = IntegralBySelf::where('ticket_id' , $input_data['ticket_id'] )->where('status', 'success')->exists();
        if($check)
        {
            throw new \Exception('该小票号已申请积分成功,请勿重复提交´');
        }
        return $result;
    }


}
