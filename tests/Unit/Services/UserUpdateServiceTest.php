<?php
/**
 * 会员迁移数据
 */
namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ShopEM\Jobs\MigratingData;
use ShopEM\Services\User\UserPassport;

class UserUpdateServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

    	$users_data = $this->userList();
        $service = new \ShopEM\Services\MigratingDataService;
        // $old_user_ids = $goods_ids = array_column($users_data, 'old_source_id');

    	DB::beginTransaction();
    	try {
    		$chunkData = array_chunk($users_data, 500);
    		foreach ($chunkData as $data) 
    		{
                $old_user_ids = [];
                $old_user_ids = array_column($data,'old_source_id');
                
                DB::table('user_accounts')->insert($data);
                //wx_userinfos
    			$wxUserInfos = $this->wxUserInfos($old_user_ids);
                $chunkInfoData = array_chunk($wxUserInfos, 500);
                foreach ($chunkInfoData as $key => $wxInfo) 
                {
                    DB::table('wx_userinfos')->insert($wxInfo);
                    $info_user_ids = [];
                    $info_user_ids = array_column($wxInfo,'old_source_id');
                    $service->upWxUserInfos($info_user_ids);
                    // MigratingData::dispatch($info_user_ids,'upWxUserInfos');
                }
                //user_addresses
                $userAddrList = $this->userAddrList($old_user_ids);
                $chunkAddrData = array_chunk($userAddrList, 500);
                foreach ($chunkAddrData as $key => $addrData) 
                {
                    DB::table('user_addresses')->insert($addrData);
                    $addr_user_ids = [];
                    $addr_user_ids = array_column($addrData,'old_source_id');
                    $service->upUserAddrList($info_user_ids);
                    // MigratingData::dispatch($addr_user_ids,'upUserAddrList');
                }
    		}
	    	DB::commit();
	    	$this->assertTrue(true);
	    	echo "成功";
	    	exit;
    	} catch (Exception $e) {
    		DB::rollBack();
    		$this->assertTrue(false);
    		echo "失败";
    		exit;
    	}
        
    }

    // user_accounts  wx_userinfos  user_addresses
    public function userList()
    {
    	$insert = [];
        $old_users = DB::connection("mysql_master")->table('user_accounts')->get()->toArray();
        
    	$new_users = DB::table('user_accounts')->select('mobile')->get()->toArray();
        $new_users = array_column($new_users,null,'mobile');

        foreach ($old_users as $key => $value) 
        {
            $data = [];
            $data = (array)$value;
            $mobile = $data['mobile'];
            if (!isset($new_users["{$mobile}"]) || empty($new_users["{$mobile}"]))
            {
                $data['old_source_id'] = $data['id'];
                unset($data['id']);
                if (UserPassport::isExistsAccount($data['login_account'], null, 'login_account')) {
                    $data['login_account'] = $this->getAccount();
                }
                $data['password'] = $this->getPassword($data['login_account']);
                $data['openid'] = null;
                $insert[] = $data;
            }
        }
    	return $insert;
    }

    /**
     * [getAccount 获取会员账号]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    private function getAccount()
    {
        $account = 'hy' . date('Ymd') . '_' . getRandStr(6);
        if (UserPassport::isExistsAccount($account, null, 'login_account')) {
            $account = $this->getAccount();
        }
        return $account;
    }

    public function getPassword($account)
    {
        $u = explode('_', $account);
        $pass = 'Hyflsc@' . substr($u[0], 2);
        $password = bcrypt($pass);
        return $password;
    }


    #微信信息
    public function wxUserInfos($user_ids)
    {
        $insert = [];
        $user_infos = DB::connection("mysql_master")->table('wx_userinfos')->whereIn('user_id',$user_ids)->get()->toArray();

        foreach ($user_infos as $key => $value) 
        {
            $data = [];
            $data = (array)$value;
            unset($data['id']);
            $data['old_source_id'] = $data['user_id'];
            $data['openid'] = '';
            $insert[] = $data;
        }
        return $insert;
    }

    #会员地址
    public function userAddrList($user_ids)
    {
        $insert = [];
        $user_addrers = DB::connection("mysql_master")->table('user_addresses')->whereIn('user_id',$user_ids)->get()->toArray();
        foreach ($user_addrers as $key => $value) 
        {
            $data = [];
            $data = (array)$value;
            unset($data['id']);
            $data['old_source_id'] = $data['user_id'];
            $insert[] = $data;
        }
        return $insert;
    }
    
}
