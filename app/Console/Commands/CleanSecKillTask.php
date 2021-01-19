<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;

class CleanSecKillTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CleanSecKillTask';

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
        //
        $service = new   \ShopEM\Services\SecKillService();

        $service->cleaningApplyGoods();//取消释放商家申请秒杀活动,但尚未通过的商品
        $service->cleaningGoodsRedis();//清除redis 秒杀商品记录
    }
}
