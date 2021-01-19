<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendSubscribeMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->queue    = 'SendSubscribeMessage';
        $this->params = $params;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sevice= new  \ShopEM\Services\SubscribeMessageService();

        $data=$this->params;

        $sevice->SendMessageAct($data);
    }


    public function failed(\Exception $exception)
    {
        //给推送失败的用户加上标识

    }
}


