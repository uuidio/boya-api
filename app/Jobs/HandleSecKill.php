<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ShopEM\Services\SecKillService;

class HandleSecKill implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $kill_orders_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($kill_orders_id)
    {
        // 未付款秒杀10分钟后关闭
        // $this->delay = now()->addMinutes(10);
        $this->delay = now()->addMinutes(3);
        
        // 队列分组名称
        $this->queue = 'HandleSecKill';

        $this->kill_orders_id = $kill_orders_id;
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

            $service= new SecKillService();

            $service->HandleSecKill($this->kill_orders_id);

        });
    }
}
