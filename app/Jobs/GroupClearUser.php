<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Services\GroupService;
use Illuminate\Support\Facades\DB;

class GroupClearUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {   //五分钟执行
        $this->delay = now()->addMinute(3);
        $this->data = $data;
        $this->queue = 'GroupClearUser';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::transaction(function () {
            $service = new GroupService();
            $service->clearGroupUser($this->data);
        });
    }
}
