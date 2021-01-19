<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\UserPointErrorLog;

class UserPointError implements ShouldQueue
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
        $this->queue = 'pointErrorLog:create';

        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 记录积分异常情况
        DB::transaction(function () {
            UserPointErrorLog::create([
                'user_id' => $this->params['user_id'],
                'tid' => $this->params['tid'],
                'behavior_type' => $this->params['type'],
                'point' => $this->params['modify_point'],
                'message' => $this->params['message']
            ]);
        });
    }
}
