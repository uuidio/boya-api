<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\TradeEstimates;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeRewards;
use ShopEM\Models\UserDeposit;
use ShopEM\Models\UserDepositLog;

class TradeRewardsTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TradeRewardsTask';

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
     * @return bool
     * @throws \Exception
     */
    public function handle()
    {
        $end_day = date('Y-m-d H:i:s', strtotime('-30 days'));

        //收货时间的30天
        $trade_orders = TradeOrder::where(['is_distribution'   => 1,
                                           'act_reward'        => 1,
                                           'after_sales_status' => null
        ])->where('end_time', '<=', $end_day)->get();

        if(count($trade_orders) <= 0){
            return true;
        }

        DB::beginTransaction();
        try {
            foreach ($trade_orders as $key_v) {
                $orders = TradeEstimates::where(['oid' => $key_v['oid'],'status'=>0])->get();

                foreach ($orders as $value) {

                    $insert_data['shop_id'] = $value['shop_id'];
                    $insert_data['goods_id'] = $value['goods_id'];
                    $insert_data['user_id'] = $value['user_id'];
                    $insert_data['pid'] = $value['pid'];
                    $insert_data['tid'] = $value['tid'];
                    $insert_data['oid'] = $value['oid'];
                    $insert_data['reward_value'] = $value['reward_value'];
                    $insert_data['type'] = $value['type'];
                    $insert_data['iord'] = $value['iord'];
                    TradeRewards::create($insert_data);

//                    UserDeposit::where('user_id', $value['pid'])->decrement('estimated', $value['reward_value']);

//                    UserDeposit::where('user_id', $value['pid'])->increment('income', $value['reward_value']);
                    $reward_value=$value['reward_value'];
                    //减少预估收益,增加实际收益
                    DB::update("update em_user_deposits set `estimated`=`estimated`-$reward_value  ,`income`=`income`+$reward_value  where user_id =".$value['pid']);

                    //将预估订单改成已完成
                    TradeEstimates::where(['id' => $key_v['id']])->update(['iord'=>2]);

                    $log['type'] = 1;
                    $log['user_id'] = $value['pid'];
                    $log['operator'] = 'admin';
                    $log['fee'] = $value['reward_value'];
                    $log['send_type'] = 1;//线上
                    $log['message'] = '会员订单分润产生收益-订单号:'.$value['tid'].'-子订单号-'.$value['oid'];
                    UserDepositLog::create($log);
                }

                //更新状态
                TradeOrder::where(['oid' => $key_v['oid']])->update(['act_reward' => 2]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $message = $e->getMessage();
            throw new \Exception($message);
        }

        return true;
    }

}
