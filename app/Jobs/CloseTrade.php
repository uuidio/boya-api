<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\TradePaybill;
use ShopEM\Services\TradeService;

class CloseTrade implements ShouldQueue
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
        // 未付款订单2小时后关闭
        $this->delay = now()->addhours(2);
        // 队列分组名称
        $this->queue = 'trade:close';

        $this->payment_id = $payment_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 关闭未付款订单
        DB::transaction(function () {
            $tids = TradePaybill::select('tid')->where('payment_id', $this->payment_id)->where('status','!=','succ')->get();
            $tradeService = new TradeService();
            foreach ($tids as $v) {
                $tradeService->PlatformQueueTradeCancel($v->tid, '系统取消');
            }
        });
    }
}
