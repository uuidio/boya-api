<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Models\WxUserinfo;
use ShopEM\Jobs\UpdateCrmUserInfo;
use ShopEM\Models\YiTianUserCard;

class UpdateCrmUserCardInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateCrmUserCardInfo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每十五分钟执行一次更新用户的会员信息任务';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $gm_ids = [];
        // $cards = YiTianUserCard::where('level','=',1)->pluck('gm_id');
        // foreach ($cards as $gm_id) {
        //     $gm_ids[] = $gm_id;
        // }
    	$users = UserRelYitianInfo::whereNotNull('yitian_id')
                ->leftJoin('wx_userinfos', 'wx_userinfos.user_id', '=', 'user_rel_yitian_infos.user_id')
                ->select('user_rel_yitian_infos.user_id','user_rel_yitian_infos.gm_id','wx_userinfos.is_update_info')
                ->where('is_update',0)
                ->where('is_update_info',1)
                ->paginate(100);
    	foreach ($users as $value) 
        {
        	$user = WxUserinfo::where('is_update_info',1)->where('user_id',$value['user_id'])->first();
        	if ($user) {
        		try {
        			$this->updateCrmUserInfoJob($value['gm_id'],$user);
        			UserRelYitianInfo::where([ 'user_id'=>$value['user_id'],'gm_id'=>$value['gm_id'] ])->update(['is_update'=>1]);
        		} catch (\Exception $e) {
        			return false;
        		}
        	}
        }
    }


    public function updateCrmUserInfoJob($gm_id,$user)
    {
    	$info = [
            'real_name'    => $user->real_name ?? '',
            'nick_name'    => $user->nickname ?? '',
            'email'        => $user->email ?? '',
            'gender'       => $user->sex ?? '',
            'dateOfBirth'  => $user->birthday ? date('Y-m-d',$user->birthday): '',
            'user_id'      => $user->user_id,
        ];
    	$service = new  \ShopEM\Services\YitianGroupServices($gm_id);
        $service->updateCrmUserInfo($info);
    }
}
