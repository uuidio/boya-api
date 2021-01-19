<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Services\YitianGroupServices;
use Illuminate\Support\Facades\DB;

class TradePush implements ShouldQueue
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
        $this->queue = 'trade';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;

        $mobile = DB::table('user_accounts')->where('id',$data['user_id'])->value('mobile');
        $gm_id = DB::table('trades')->where('tid',$data['receiptNo'])->value('gm_id');
        DB::table('trades')->where('tid',$data['receiptNo'])->update(['push_crm'=>1]);

        $yitianGroupServices = new YitianGroupServices($gm_id);
        $yitianGroupServices->updateCardTypeCode( $data['user_id'], $mobile);

        $yitianFilter = [
            'user_id' => $data['user_id'],
            'gm_id'   => $gm_id,
        ];
        $card_code = DB::table('user_rel_yitian_infos')->where($yitianFilter)->value('card_code');
        $data['cardCode'] = $card_code;

        $res = $yitianGroupServices->tradePushCrm($data);
        if ($res['Data']['RewardList']) 
        {
            $point = ($res['Data']['RewardList'][0]['Balance'] ?? 0) - ($res['Data']['RewardList'][0]['PreviousBalance'] ?? 0);
            DB::table('trades')->where('tid',$data['receiptNo'])->update([
                'push_crm'          => 2,
                'obtain_point_fee'  =>number_format($point)
            ]);
            DB::table('user_point_logs')->where('id',$data['log_id'])->update([
                'push_crm'   => 2,
                'point'      => number_format($point),
                'log_type'   => 'trade',
                'log_obj'    => $data['receiptNo'],
            ]);
        }
    }
}
