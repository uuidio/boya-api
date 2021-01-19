<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;

class Trademonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Trademonth';

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
        $statService = new   \ShopEM\Services\TradeSettleService();
//        $params = array(
//            'time_start'  => '2019-11-01 00:00:00',
//            'time_end'    => '2019-11-30 23:59:59',
//            'time_insert' => time(),
//        );
        $params=[];
        $statService->tradeMonth($params);
    }
}
