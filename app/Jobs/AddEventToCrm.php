<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use ShopEM\Events\Event;
use ShopEM\Services\User\UserCrmConnect;

class AddEventToCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $param;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data = [])
    {
        $this->queue    = 'addEvent';
        $this->param = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UserCrmConnect $user)
    {
        $user->addEvent($this->param);
    }

    public function failed()
    {
        EventLog::dispatch($this->param);
    }

}

