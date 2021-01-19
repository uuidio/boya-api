<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;

class CheckActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CheckActivity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日检查活动状态';

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
        $Service = new \ShopEM\Services\Marketing\Activity();
        $Service->checkStatus();
    }
}
