<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ShopEM\Services\YitianGroupErpServices;

use ShopEM\Models\Shop;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeRefunds;

class TradePushErp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    protected $tid;

    protected $trade_type;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tid,$trade_type='add')
    {
        $this->tid  = $tid;
        $this->trade_type  = $trade_type;
        $this->queue = 'trade:pusherp';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //如果是售后这个是子订单
        $tid = $this->tid; 
        $trade_type = $this->trade_type;

        $tradeFilter = [];
        if ($trade_type == 'refund') {
            $tradeFilter['oid'] = $tid;
        }else{
            $tradeFilter['tid'] = $tid;
        }

        $shop = Shop::where('id', function ($query) use ($tradeFilter) {
            $query->select('shop_id')
                ->from(with(new TradeOrder)->getTable())
                ->where($tradeFilter)->limit(1);
        })->select('id','erp_storeCode','erp_posCode','is_push_erp')->first();

        if($shop['is_push_erp'] == 0){
            return false;
        }

        if ($trade_type == 'add' || $trade_type == 'cancel') 
        {
            $tradeOrder = TradeOrder::where($tradeFilter)->whereNotNull('pay_time')->get()->toArray();
            foreach ($tradeOrder as $order) 
            {
                if ($trade_type == 'cancel') 
                {
                    $order['amount'] = 0 - $order['amount']; 
                }
                $this->pushData($order,$shop);
                
            }
        }

        if ($trade_type == 'refund') 
        {
            $order = TradeOrder::where($tradeFilter)->whereNotNull('pay_time')->first();
            $tradeFilter['status'] = '1';
            $refund_fee = TradeRefunds::where($tradeFilter)->value('refund_fee');
            $order = $order->toArray();
            $order['amount'] = 0 ;
            if ($refund_fee > 0) {
                $order['amount'] = 0 - $refund_fee; 
            }
            $this->pushData($order,$shop);
        }
        
    }




    public function pushData($order,$shop)
    {
        $service = new YitianGroupErpServices;
        $order['erp_storeCode'] = $shop->erp_storeCode;
        $order['erp_posCode']   = $shop->erp_posCode;
        if (empty($order['erp_storeCode']) || empty($order['erp_posCode'])) {
            $info = '子订单:'.$order['oid'].'(未配置商家信息，无法推送erp)';
            $this->errorLog($info);
            return true;
        }
        $service->upLoadTransData($order);
    }


    /**
     * 错误日志记录
     * @param $info
     */
    public function errorLog($info)
    {
        $filename = storage_path('logs/' . 'yapi-erp-trade-' . date('Y-m-d') . '.log');
        file_put_contents($filename, '[' . date('Y-m-d H:i:s') . '] ' . print_r($info, true) . "\n", FILE_APPEND);
    }
}
