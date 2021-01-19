<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;

class UVDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UVDay';

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

        $statService = new   \ShopEM\Services\UVStatisticsService();
        $day = "-1";
        $params = array(
            'time_start'  => date('Y-m-d 00:00:00', strtotime($day . ' day')),
            'time_end'    => date('Y-m-d 23:59:59', strtotime($day . ' day')),
            'time_insert' => date('Y-m-d', strtotime($day . ' day')),
        );

        $statService->YesterdayData($params);
    }
}
