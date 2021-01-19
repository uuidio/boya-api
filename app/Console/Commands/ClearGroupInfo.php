<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;

class ClearGroupInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ClearGroupInfo';

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
        $groupService = new   \ShopEM\Services\GroupService();

        $groupService->clearGroupGoods();  //清除商品库存
        $groupService->clearGroupInfoTask();  //清除订单缓存,跟新订单状态
    }
}
