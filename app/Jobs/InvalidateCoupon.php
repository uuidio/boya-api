<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Services\Marketing\Coupon;

class InvalidateCoupon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $stock_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($stock_id)
    {
        // 队列分组名称
        $this->queue = 'coupon:invalidate';
        $this->stock_id = $stock_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = new Coupon();
        $service->invalidateCoupon($this->stock_id);
    }
}
