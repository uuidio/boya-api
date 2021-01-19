<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Models\YiTianUserCard;
use ShopEM\Jobs\UpdateCrmUserInfo;

class UpdateUserYitianId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateUserYitianId';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新用户的益田id';

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
        $gm_ids = [];
        $cards = YiTianUserCard::where('level','=',1)->pluck('gm_id');
        foreach ($cards as $gm_id) {
            $gm_ids[] = $gm_id;
        }
        $users = UserRelYitianInfo::where(function ($query) use ($gm_ids){
            $query->whereIn('gm_id',$gm_ids)->whereNull('yitian_id');
        })->orWhere(function ($query) use ($gm_ids) {
            $query->whereNull('yitian_card_id')->whereIn('gm_id',$gm_ids);
        })->paginate(50);
        foreach ($users as $user) 
        {
            $service = new  \ShopEM\Services\YitianGroupServices($user['gm_id']);
            if (!empty($user['yitian_id']) && empty($user['yitian_card_id']) ){
                $this->updateCrmUserInfoJob($user);
            }
            $respon = $service->signUpNotExistedMember($user['mobile']);
            if ($respon) {
                UserRelYitianInfo::where('id',$user['id'])->update(['yitian_id' => $respon['member_id'], 'new_yitian_user' => $respon['not_existed']]);
                // UserAccount::where('id', $user['id'])->update(['yitian_id' => $respon['member_id'], 'new_yitian_user' => $respon['not_existed']]);
            }

        }
    }

    public function updateCrmUserInfoJob($user)
    {
        $data['user_id']= $user['user_id'];
        $data['mobile'] = $user['mobile'];
        $data['gm_id']  = $user['gm_id'];
        $data['updateCardTypeCode'] = true;
        UpdateCrmUserInfo::dispatch($data);
    }
}
