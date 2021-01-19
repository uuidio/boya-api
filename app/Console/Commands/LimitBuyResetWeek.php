<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;

class LimitBuyResetWeek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LimitBuyResetWeek';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每周限购次数重置';

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
        $limitBuyService = new   \ShopEM\Services\LimitBuyService();
        $limitBuyService->reset(2);
    }
}
