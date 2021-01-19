<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;

class UpdateUserCardTypeCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateUserCardTypeCode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每月1号更新用户的会员卡信息';

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
        $ids = GmPlatform::pluck('gm_id');
        foreach ($ids as $key => $gm_id) 
        {
            $service = new \ShopEM\Services\YitianGroupServices($gm_id);
            $service->updateUsersCardTypeCode();
        }

    }
}
