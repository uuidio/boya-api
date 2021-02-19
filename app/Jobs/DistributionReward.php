<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use ShopEM\Services\TradeService;

class DistributionReward implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payment_id)
    {
        // 队列分组名称
        $this->queue = 'DisReward';
        $this->payment_id = $payment_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('DistributionReward success');
        storageLog($this->payment_id,'success');
        // 拆分订单
        $tradeSpitService = new TradeService();
        //推物分润
        $tradeSpitService->DistributionReward($this->payment_id);
        //分销分成
        $tradeSpitService->DistributionProfiles($this->payment_id);
    }
}
