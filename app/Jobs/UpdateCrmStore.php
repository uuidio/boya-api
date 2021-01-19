<?php

/**
 * @Author: nlx
 * @Date:   2020-04-20 15:53:52
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-04-21 11:02:42
 */
namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class UpdateCrmStore implements ShouldQueue
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
        $this->queue = 'crmupdate:store';

        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
    	$gm_id = $this->params['gm_id'];
    	if ($gm_id>0) {
    		$service = new \ShopEM\Services\YitianGroupServices($gm_id);
        	$lists = $service->masterStoreList();
            if ($lists && is_array($lists)) {
            	$storeService = new \ShopEM\Services\Yitian\StoreService();
            	$storeService->saveList($lists,$gm_id);
            }
    	}
    	
    }
}