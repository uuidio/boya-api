<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use ShopEM\Services\TradeService;

class ConfirmReceiptTrade implements ShouldQueue
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
        // 队列分组名称
        $this->queue = 'trade:confirmReceipt';

        $this->tid = $tid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 自动确认收货
        DB::transaction(function () {
            $TradeService = new TradeService;
            $TradeService->confirmReceiptCommands($this->tid);
        });
    }
}
