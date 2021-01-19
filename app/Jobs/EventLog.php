<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Events\Event;

class EventLog implements ShouldQueue
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
        $this->queue = 'EventLog:create';

        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Event::create([
            'mobile' => $this->params['mobile'],
            'group_number' => $this->params['group_number'],
            'score' => $this->params['score'] ?? 0,
            'event' => $this->params['event'],
            'is_push_success' => 2
        ]);
    }
}
