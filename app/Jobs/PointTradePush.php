<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Services\YitianGroupServices;
use Illuminate\Support\Facades\DB;

class PointTradePush implements ShouldQueue
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
        $this->queue = 'trade:pointPush';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        $user = DB::table('integral_by_selves')->where('id',$data['integral_id'])->select('gm_id','user_id','mobile')->first();
        $gm_id = $user->gm_id;

        $yitianGroupServices = new YitianGroupServices($gm_id);
        $yitianGroupServices->updateCardTypeCode( $user->user_id, $user->mobile);

        $yitianFilter = [
            'user_id' => $user->user_id,
            'gm_id'   => $gm_id,
        ];
        $card_code = DB::table('user_rel_yitian_infos')->where($yitianFilter)->value('card_code');
        $data['cardCode'] = $card_code;

        $res = $yitianGroupServices->tradePushCrm($data);
        if ($res['Data']['RewardList'])
        {
            $point = ($res['Data']['RewardList'][0]['Balance'] ?? 0) - ($res['Data']['RewardList'][0]['PreviousBalance'] ?? 0);
            DB::table('user_point_logs')->where('id',$data['log_id'])->update([
                'push_crm'   => 2,
                'point'      => number_format($point),
                'log_type'   => $data['log_type']??'selfIncr',
                'log_obj'    => $data['integral_id'],
            ]);
        }

        //增加推送记录
        if (isset($res['Result']) && $res['Result']['HasError'] == true)
        {
            $error['push_crm'] = 3;
            $error['crm_msg'] = $res['Result']['ErrorMessage'];
            DB::table('integral_by_selves')->where('id',$data['integral_id'])->update($error);
        }
        if (isset($res['Data']['RewardList']) && $res['Data']['RewardList'])
        {
            $correct['push_crm'] = 2;
            DB::table('integral_by_selves')->where('id',$data['integral_id'])->update($correct);

        }

    }
}
