<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Services\YitianGroupServices;
use ShopEM\Models\UserRelYitianInfo;

class UpdateCrmUserInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data  = $data;
        $this->queue = 'user:update';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //更新用户的会员卡信息
        if (isset($this->data['updateCardTypeCode']) && $this->data['updateCardTypeCode']) 
        {
            $yitianGroupServices = new YitianGroupServices($this->data['gm_id']);
            $yitianGroupServices->updateCardTypeCode($this->data['user_id'],$this->data['mobile']);
            return true;
        }

        //更新会员资料
        $gm_ids = UserRelYitianInfo::where('user_id',$this->data['user_id'])->pluck('gm_id');
        foreach ($gm_ids as $key => $gm_id) 
        {
            $yitianGroupServices = new YitianGroupServices($gm_id);
            $yitianGroupServices->updateCrmUserInfo($this->data);
        }
    }
}
