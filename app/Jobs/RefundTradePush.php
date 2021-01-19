<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Services\YitianGroupServices;
use Illuminate\Support\Facades\DB;

class RefundTradePush implements ShouldQueue
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
        $this->queue = 'trade:refund';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $gm_id = DB::table('trade_refunds')->where('oid',$this->data['receiptno'])->value('gm_id');
        $yitianGroupServices = new YitianGroupServices($gm_id);
        $res = $yitianGroupServices->refundPushCrm($this->data);
        if (isset($res['Data']['success'])) {
            $refundpoint = (int)$res['Data']['refundpoint'];
            DB::table('trade_refunds')->where('oid',$this->data['receiptno'])->update([ 'refund_point'=>$refundpoint ]);
            DB::table('user_point_logs')->where('id',$this->data['log_id'])->update([
                'push_crm'   => 2,
                'point'      => $refundpoint,
                'log_type'   => 'trade',
                'log_obj'    => $this->data['org_receiptno'],
            ]);
        }
    }
}
