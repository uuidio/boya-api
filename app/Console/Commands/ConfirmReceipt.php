<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Jobs\ConfirmReceiptTrade;
use ShopEM\Models\Trade;
use ShopEM\Services\TradeService;
use ShopEM\Models\GmPlatform;

class ConfirmReceipt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ConfirmReceipt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动确认收货';

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
        $repository = new \ShopEM\Repositories\ConfigRepository;

        $ids = GmPlatform::where('status',1)->pluck('gm_id');
        foreach ($ids as $key => $gm_id) 
        {
            $config = $repository->configItem('shop', 'trade', $gm_id);
            if (isset($config['trade_finish_spacing_time']) && $config['trade_finish_spacing_time']['value']) 
            {
                $day = '-'.$config['trade_finish_spacing_time']['value'].' day';
                $end_day = date('Y-m-d H:i:s', strtotime($day));
                $cancel_status = ['NO_APPLY_CANCEL', 'FAILS'];
                $trade_arr = Trade::where('status', 'WAIT_BUYER_CONFIRM_GOODS')->where('gm_id',$gm_id)->where('pick_type', '!=', 1)->whereIn('cancel_status', $cancel_status)->whereDate('consign_time', '<', $end_day)->get();
                if ($trade_arr) {
                    foreach ($trade_arr as $trade) {
                       // $TradeService->confirmReceiptCommands($trade['tid']);
                        ConfirmReceiptTrade::dispatch($trade['tid']);

                    }
                }
            }
        }



    }
}
