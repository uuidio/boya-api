<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Trade;
use ShopEM\Services\TradeAfterSalesService;
use ShopEM\Models\GmPlatform;

class ShutSendTimeoutApply extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ShutSendTimeoutApply';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '关闭回寄超时的售后申请';

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
        $repository = new \ShopEM\Repositories\ConfigRepository;
        $ids = GmPlatform::where('status',1)->pluck('gm_id');

        foreach ($ids as $key => $gm_id) 
        {
            $config = $repository->configItem('shop', 'trade', $gm_id);
            if (isset($config['send_timeout']) && $config['send_timeout']['value']) 
            {
                $this->takeShutSend($gm_id,$config);
            }
        }
        
        return true;
    }

    public function takeShutSend($gm_id,$config)
    {
        $service = new TradeAfterSalesService();
        $day = '-'.$config['send_timeout']['value'].' day';
        $end_day = date('Y-m-d H:i:s', strtotime($day));

        $applys = DB::table('trade_aftersales')
            ->where('progress', '1')
            ->where('gm_id', $gm_id)
            ->get()->toArray();
        if (!empty($applys)) {
            foreach ($applys as $apply) {
                $apply = (array)$apply;

                $created_at = DB::table('trade_aftersale_logs')
                    ->where('oid', $apply['oid'])
                    ->where('progress', 1)
                    ->orderBy('created_at', 'desc')
                    ->value('created_at');
                if ($created_at < $end_day) {
                    $refundsData['aftersales_bn'] = $filter['aftersales_bn'] = $apply['aftersales_bn'];
                    try {
                        $service->doValidation($filter, 'false', '超出回寄期限', $refundsData, 0, '', true); //超出回寄期限,驳回申请
                    } catch (\Exception $e) {
                        throw new \LogicException($e->getMessage());
                    }

                }
            }
        }
    }
}
