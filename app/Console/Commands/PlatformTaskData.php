<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Models\GmPlatform;

class PlatformTaskData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PlatformTaskData';

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
        $statService = new   \ShopEM\Services\StatService();
        $day = "-1";
        $ids = GmPlatform::pluck('gm_id');
        foreach ($ids as $key => $gm_id) 
        {
            $params = array(
                'time_start'  => date('Y-m-d 00:00:00', strtotime($day . ' day')),
                'time_end'    => date('Y-m-d 23:59:59', strtotime($day . ' day')),
                'time_insert' => date('Y-m-d H:i:s', strtotime($day . ' day')),
                'gm_id'       => $gm_id,
            );
            $statService->platformTaskData($params);
        }
    }
}
