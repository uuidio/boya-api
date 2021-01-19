<?php

/**
 * ClearQrcodeStore.php
 * @Author: nlx
 * @Date:   2020-05-14 17:42:59
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-07-28 16:18:59
 */
namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Models\QrcodeStore;

class ClearQrcodeStore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ClearQrcodeStore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定时清除二维码图片';

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
        //清除数据库保存的二维码
        QrcodeStore::clearQr();
        //清除缓存文件夹内的二维码
    	QrcodeStore::clearTempQr();
    }
}