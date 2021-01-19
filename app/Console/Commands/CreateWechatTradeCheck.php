<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeRefunds;
use ShopEM\Services\TradeCheckService;
use Illuminate\Support\Facades\DB;

class CreateWechatTradeCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wechat:trade:check {--day= : 日期} {--start= : 开始日期} {--stop= : 结束日期} {--begin= : 开始时间} {--end= : 结束时间} {--type=today : 时间模式}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成微信对账表数据';

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
        $this->info('begin...');
        $this->info('pay data:');
        $option = $this->options();
        switch ($option['type']) {
            case 'day':
                $start = Carbon::createFromFormat('Y/m/d H:i:s',$option['day'].' 00:00:00');
                $stop = Carbon::createFromFormat('Y/m/d H:i:s',$option['day'].' 23:59:59');
                break;

            case 'date':
                $start = Carbon::createFromFormat('Y/m/d H:i:s',$option['start'].' 00:00:00');
                $stop = Carbon::createFromFormat('Y/m/d H:i:s',$option['stop'].' 23:59:59');
                break;

            case 'time':
                $start = Carbon::createFromFormat('Y/m/d H:i:s',$option['begin']);
                $stop = Carbon::createFromFormat('Y/m/d H:i:s',$option['end']);
                break;

            default:
                $start = now()->floorDay()->subDay();
                $stop = now()->ceilDays()->subDay()->subSecond();
                break;
        }
        $service = new TradeCheckService();
        $bills = TradePaybill::where('status','succ')->whereBetween('payed_time',[$start->toDateTimeString(),$stop->toDateTimeString()])->get();
        $bar = $this->output->createProgressBar(count($bills));
        $bar->start();
        foreach ($bills as $bill) {
            $service->createPayData($bill);
            $bar->advance();
        }
        $bar->finish();
        $this->info(PHP_EOL.'refund data:');
        $refunds = TradeRefunds::where('status','1')->whereBetween('refund_at',[$start->toDateTimeString(),$stop->toDateTimeString()])->get();
        $bar = $this->output->createProgressBar(count($refunds));
        $bar->start();
        foreach ($refunds as $refund) {
            $service->createRefundData($refund);
            $bar->advance();
        }
        $bar->finish();
        $this->info(PHP_EOL.'finish!');
    }
}
