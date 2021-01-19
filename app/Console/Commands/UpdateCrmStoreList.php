<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Jobs\UpdateCrmStore;
use ShopEM\Models\GmPlatform;

class UpdateCrmStoreList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateCrmStoreList';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天午夜更新CRM店铺列表数据';

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
        $ids = GmPlatform::where('status',1)->pluck('gm_id');
        foreach ($ids as $key => $gm_id) 
        {
        	UpdateCrmStore::dispatch(['gm_id'=>$gm_id]);
        }

    }
}
