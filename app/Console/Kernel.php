<?php

namespace ShopEM\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        Commands\ShopTaskData::class,
        Commands\PlatformTaskData::class,
//        Commands\LimitBuyResetDay::class,
//        Commands\LimitBuyResetMonth::class,
//        Commands\LimitBuyResetWeek::class,
        Commands\CleanSecKillTask::class,
        Commands\ClearGroupInfo::class,
        // Commands\CheckActivity::class,
        Commands\TradeTid::class,
        Commands\TradeDay::class,
        Commands\Trademonth::class,
        Commands\UpdateUserCardTypeCode::class,
        Commands\ConfirmReceipt::class,
        Commands\UpdateUserYitianId::class,
        Commands\UpdateCrmUserCardInfo::class,
        Commands\UpdateCrmStoreList::class,
        Commands\UVDay::class,
        Commands\ClearQrcodeStore::class,
        Commands\UserRelatedLogs::class,
        Commands\ClearDownloadLog::class,
        Commands\CreateWechatTradeCheck::class,
        Commands\ShutSendTimeoutApply::class,
        Commands\ResetPayPasswordError::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        // 采用类型进行调用
//        $schedule->command(test::class)->everyMinute();

//        $schedule->command('ShopTaskData')->everyMinute();//测试
//        $schedule->command('PlatformTaskData')->everyMinute();//测试

        $schedule->command('ShopTaskData')->dailyAt('02:15');//每天凌晨3点运行任务,生成前一天数据
        $schedule->command('PlatformTaskData')->dailyAt('02:00');//每天3点运行任务,生成前一天数据
//        $schedule->command('LimitBuyResetDay')->daily();//每天午夜运行任务
//        $schedule->command('LimitBuyResetMonth')->monthlyOn(1,'00:00');//每月1号午夜运行任务
//        $schedule->command('LimitBuyResetWeek')->weekly()->mondays()->at('00:00');//每周一午夜运行任务
        // $schedule->command('CheckActivity')->daily();//每天午夜运行任务

        $schedule->command('ClearGroupInfo')->everyMinute();//团购

        $schedule->command('CleanSecKillTask')->everyMinute();//清除秒杀redis 数据

        //$schedule->command('TradeTid')->dailyAt('00:10');//生成日结明细数据,每天凌晨0点过10分运行任务
        //$schedule->command('TradeDay')->dailyAt('01:00');//生成日结,每天凌晨1点运行任务
        $schedule->command('TradeTid')->everyMinute();//生成日结明细数据,每天凌晨0点过10分运行任务
        $schedule->command('TradeDay')->everyMinute();//生成日结,每天凌晨1点运行任务


        $schedule->command('Trademonth')->monthlyOn(1, '03:00');//生成月结,每月1号凌晨3点运行任务

        $schedule->command('UpdateUserCardTypeCode')->monthlyOn(1, '04:00');//每月1号凌晨3点运行任务

        $schedule->command('ConfirmReceipt')->dailyAt('01:00');//每天一点执行

        $schedule->command('UpdateUserYitianId')->everyFiveMinutes();//五分钟执行一次
        $schedule->command('UpdateCrmUserCardInfo')->everyFifteenMinutes();//每十五分钟执行一次任务

        // $schedule->command('UpdateCrmStoreList')->everyMinute();
        $schedule->command('UpdateCrmStoreList')->daily();//每天午夜运行任务
        $schedule->command('wechat:trade:check')->daily();//每天午夜运行微信对账数据生成任务
        $schedule->command('UVDay')->dailyAt('05:00');//生成统计交易人数,每天凌晨5点运行任务

        $schedule->command('ClearQrcodeStore')->dailyAt('2:30');//每天2:30点运行任务

        $schedule->command('UserRelatedLogs')->everyMinute();//过期绑定推广关系
        $schedule->command('ClearDownloadLog')->everyFiveMinutes(); //五分钟执行一次
        $schedule->command('ShutSendTimeoutApply')->hourly();//每个小时执行一次，关闭回寄超时的售后申请
        $schedule->command('ResetPayPasswordError')->dailyAt('1:00');//每天1点运行任务
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
