<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ShopEM\Jobs\TestJob;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        TestJob::dispatch('test job 消息')->delay(now()->addMinutes(1));
    }
}
