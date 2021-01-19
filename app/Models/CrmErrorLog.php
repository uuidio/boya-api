<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Services\User\UserCrmConnect;

class CrmErrorLog implements ShouldQueue
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
        $this->queue = 'crmApiErrorLog:create';

        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UserCrmConnect $user)
    {
        // 记录异常情况
        $user->errorLog($this->params['api_url'], $this->params['post_data'], $this->params['type'], $this->params['message']);
    }
}
