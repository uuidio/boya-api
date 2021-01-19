<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use ShopEM\Services\TradeService;

class CloseSecKillTrade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tid)
    {
        // 未付款属于秒杀的订单,15分钟后关闭
        // $this->delay = now()->addMinute(15);

        $this->delay = now()->addMinute(5);

        // 队列分组名称
        $this->queue = 'CloseSecKillTrade';

        $this->tid = $tid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 关闭属于秒杀的未付款订单
        DB::transaction(function () {

            $tradeService = new TradeService();

            $tradeService->PlatformQueueTradeCancel($this->tid, '系统取消');

        });
    }
}
