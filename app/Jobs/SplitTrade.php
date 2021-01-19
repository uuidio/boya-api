<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class SplitTrade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected  $payment_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payment_id)
    {
        // 队列分组名称
        $this->queue = 'SplitTrade';
        $this->payment_id = $payment_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 拆分订单
        DB::transaction(function () {
            //
//            testLog($this->payment_id);
            $tradeSpitService= new \ShopEM\Services\TradeSpitService();

            $tradeSpitService->setPayment($this->payment_id);
        });
    }
}
