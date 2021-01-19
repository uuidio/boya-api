<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Models\DownloadLog;

class ClearDownloadLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ClearDownloadLog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出队列失败更新';

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
        $failTime = date('Y-m-d H:i:s', strtotime("-5 minutes"));
        DownloadLog::where('status',0)->where('updated_at','>',$failTime)->update(['status'=>2]);
    }
}
