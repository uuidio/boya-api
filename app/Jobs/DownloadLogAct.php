<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;


class DownloadLogAct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        // 队列分组名称
        $this->queue = 'downloadLogAct';

        $this->params = $params;
    }

    /**
     * 超时时间。
     *
     * @var int
     */
    public $timeout = 240;


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        DB::transaction(function ()
        {
            $service =  new  \ShopEM\Services\DownloadService();
            $service->Acting($this->params);
        });
    }
}
